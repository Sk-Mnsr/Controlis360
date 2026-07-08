<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Entités</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Membres métier (responsable et agent) par entité
                </p>
            </div>
            <div class="flex gap-2">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher une entité..."
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-500"
                />
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    @click="loadEntities"
                >
                    Actualiser
                </button>
            </div>
        </div>

        <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Chargement...</div>

        <div v-else-if="!filteredEntities.length" class="p-10 text-center text-sm text-slate-500">
            Aucune entité trouvée
        </div>

        <div v-else class="divide-y divide-slate-200">
            <article
                v-for="entity in filteredEntities"
                :key="entity.id"
                class="p-4 sm:p-5"
            >
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ entity.name }}</h3>
                        <p class="text-sm text-slate-500">
                            {{ entity.type_fr ?? entity.type }}
                            <span v-if="environmentName(entity)"> — {{ environmentName(entity) }}</span>
                        </p>
                    </div>
                    <span class="mt-2 inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 sm:mt-0">
                        {{ entity.members.length }} membre(s)
                    </span>
                </div>

                <div v-if="!entity.members.length" class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Aucun responsable ou agent rattaché à cette entité
                </div>

                <table v-else class="mt-4 w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-600">
                        <tr>
                            <th class="pb-2 pr-4 font-medium">Nom</th>
                            <th class="pb-2 pr-4 font-medium">E-mail</th>
                            <th class="pb-2 font-medium">Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="member in entity.members"
                            :key="member.id"
                            class="border-b border-slate-100 last:border-0"
                        >
                            <td class="py-2.5 pr-4 font-medium">{{ member.name }}</td>
                            <td class="py-2.5 pr-4 text-slate-600">{{ member.email }}</td>
                            <td class="py-2.5">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :class="roleBadgeClass(member.metier_role)"
                                >
                                    {{ member.metier_role_fr ?? memberRoleLabel(member.metier_role) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../api/client';

const loading = ref(true);
const entities = ref([]);
const search = ref('');

const filteredEntities = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) {
        return entities.value;
    }

    return entities.value.filter((entity) =>
        entity.name?.toLowerCase().includes(term)
        || entity.code?.toLowerCase().includes(term)
        || environmentName(entity)?.toLowerCase().includes(term)
        || entity.members.some(
            (member) =>
                member.name?.toLowerCase().includes(term)
                || member.email?.toLowerCase().includes(term),
        ),
    );
});

function environmentName(entity) {
    return entity.environment?.name ?? entity.Environment?.name ?? null;
}

function memberRoleLabel(role) {
    if (role === 'responsable_entite') {
        return 'Responsable entité';
    }

    if (role === 'agent') {
        return 'Agent';
    }

    return role ?? '—';
}

function roleBadgeClass(role) {
    if (role === 'responsable_entite') {
        return 'bg-emerald-100 text-emerald-800';
    }

    return 'bg-slate-100 text-slate-700';
}

function extractEntities(responseData) {
    const root = responseData?.data ?? responseData;
    return Array.isArray(root) ? root : [];
}

async function loadEntities() {
    loading.value = true;

    try {
        const { data } = await api.get('/entities/with-members');
        entities.value = extractEntities(data);
    } finally {
        loading.value = false;
    }
}

onMounted(loadEntities);
</script>
