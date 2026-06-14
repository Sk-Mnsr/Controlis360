<template>
    <div class="space-y-6">
        <!-- Liste (super admin sans environnement sélectionné) -->
        <template v-if="isSuperAdmin && !environmentId">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold">Liste des environnements</h2>
                    <p class="mt-1 text-sm text-slate-500">Sélectionnez un environnement pour gérer ses entités</p>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                        @click="loadEnvironments"
                    >
                        Actualiser
                    </button>
                    <RouterLink
                        :to="{ name: 'environments.create' }"
                        class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800"
                    >
                        + Nouveau
                    </RouterLink>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-4">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Rechercher un environnement"
                        class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
                    />
                </div>

                <div v-if="listLoading" class="p-8 text-center text-sm text-slate-500">Chargement...</div>

                <table v-else class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nom</th>
                            <th class="px-4 py-3 font-medium">Code</th>
                            <th class="px-4 py-3 font-medium">Entités</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="env in filteredEnvironments"
                            :key="env.id"
                            class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                            @click="openEnvironment(env.id)"
                        >
                            <td class="px-4 py-3 font-medium text-violet-700">{{ env.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ env.code }}</td>
                            <td class="px-4 py-3">{{ env.entities?.length ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2" @click.stop>
                                    <button
                                        type="button"
                                        class="rounded border border-slate-300 px-2 py-1 text-xs hover:bg-slate-100"
                                        @click="openEnvironment(env.id)"
                                    >
                                        Configurer
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50"
                                        @click="removeEnvironment(env)"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filteredEnvironments.length">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">Aucun environnement trouvé</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- Détail environnement : entités uniquement -->
        <template v-else>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <button
                        v-if="isSuperAdmin"
                        type="button"
                        class="mb-2 text-sm text-slate-500 hover:text-slate-800"
                        @click="backToList"
                    >
                        ← Retour à la liste
                    </button>
                    <p class="text-sm text-slate-500">Environnement</p>
                    <h2 class="text-xl font-semibold">{{ environment?.name ?? 'Chargement...' }}</h2>
                </div>
                <RouterLink
                    v-if="isSuperAdmin && environment"
                    :to="{ name: 'environments.edit', params: { id: environment.id } }"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                >
                    Modifier
                </RouterLink>
            </div>

            <EnvironmentEntitiesPanel
                v-if="environment"
                :environment="environment"
                :entities="entities"
                @refresh="loadWorkspace"
            />

            <div v-else-if="!detailLoading" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                Environnement introuvable.
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import EnvironmentEntitiesPanel from './EnvironmentEntitiesPanel.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');
const environmentId = computed(() => route.params.id ?? null);

const environments = ref([]);
const environment = ref(null);
const entities = ref([]);
const listLoading = ref(false);
const detailLoading = ref(false);
const search = ref('');

const filteredEnvironments = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return environments.value;
    return environments.value.filter(
        (env) => env.name.toLowerCase().includes(term) || env.code?.toLowerCase().includes(term),
    );
});

function extractRecord(payload, key) {
    const data = payload?.data ?? payload;
    return data?.[key] ?? data?.[key.charAt(0).toUpperCase() + key.slice(1)] ?? null;
}

async function loadEnvironments() {
    listLoading.value = true;
    try {
        const { data } = await api.get('/environments');
        environments.value = data.data?.data ?? data.data ?? [];
    } finally {
        listLoading.value = false;
    }
}

async function loadWorkspace() {
    if (!environmentId.value) return;

    detailLoading.value = true;
    try {
        const [envRes, entRes] = await Promise.all([
            api.get(`/environments/${environmentId.value}`),
            api.get(`/entities/by-environment/${environmentId.value}`),
        ]);
        environment.value = extractRecord(envRes.data, 'environment');
        entities.value = entRes.data.data ?? entRes.data ?? [];
    } finally {
        detailLoading.value = false;
    }
}

function openEnvironment(id) {
    router.push({ name: 'environments.detail', params: { id } });
}

function backToList() {
    router.push({ name: 'environments' });
}

async function removeEnvironment(env) {
    if (!confirm(`Supprimer l'environnement « ${env.name} » ?`)) return;
    await api.delete(`/environments/${env.id}`);
    await loadEnvironments();
}

watch(environmentId, (id) => {
    if (id) {
        loadWorkspace();
    } else if (isSuperAdmin.value) {
        environment.value = null;
        loadEnvironments();
    }
});

onMounted(() => {
    if (environmentId.value) {
        loadWorkspace();
    } else if (isSuperAdmin.value) {
        loadEnvironments();
    }
});
</script>
