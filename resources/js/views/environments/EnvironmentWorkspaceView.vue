<template>
    <div class="space-y-6">
        <!-- Liste (super admin / admin multi-environnements) -->
        <template v-if="showEnvironmentsList">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold">Liste des environnements</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ isSuperAdmin
                            ? 'Sélectionnez un environnement pour gérer ses entités'
                            : 'Sélectionnez un environnement rattaché à votre compte' }}
                    </p>
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
                        v-if="isSuperAdmin"
                        :to="{ name: 'environments.create' }"
                        class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800"
                    >
                        + Nouvel environnement
                    </RouterLink>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Rechercher par nom ou code..."
                        class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
                        @input="onSearchInput"
                    />
                    <select
                        v-model.number="perPage"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
                        @change="changePerPage"
                    >
                        <option :value="8">8 / page</option>
                        <option :value="15">15 / page</option>
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                    </select>
                </div>

                <div v-if="listLoading" class="p-8 text-center text-sm text-slate-500">Chargement...</div>

                <template v-else>
                    <table class="w-full text-left text-sm">
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
                                v-for="env in environments"
                                :key="env.id"
                                class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                                @click="openEnvironment(env.id)"
                            >
                                <td class="px-4 py-3 font-medium text-violet-700">{{ env.name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ env.code }}</td>
                                <td class="px-4 py-3">{{ entitiesCount(env) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div
                                        class="env-menu"
                                        :class="{ open: openMenuId === env.id }"
                                        @click.stop
                                    >
                                        <button
                                            type="button"
                                            class="env-menu-trigger"
                                            :aria-expanded="openMenuId === env.id"
                                            aria-haspopup="menu"
                                            aria-label="Actions"
                                            @click.stop="toggleMenu(env, $event)"
                                        >
                                            <span aria-hidden="true">⋮</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!environments.length">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">Aucun environnement trouvé</td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-if="total > 0"
                        class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-sm text-slate-500">
                            {{ fromItem }}–{{ toItem }} sur {{ total }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="page <= 1 || listLoading"
                                @click="goToPage(page - 1)"
                            >
                                Précédent
                            </button>
                            <button
                                v-for="pageNumber in visiblePages"
                                :key="pageNumber"
                                type="button"
                                class="min-w-9 rounded-lg border px-3 py-1.5 text-sm"
                                :class="pageNumber === page
                                    ? 'border-violet-700 bg-violet-700 text-white'
                                    : 'border-slate-300 hover:bg-slate-50'"
                                :disabled="listLoading"
                                @click="goToPage(pageNumber)"
                            >
                                {{ pageNumber }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="page >= lastPage || listLoading"
                                @click="goToPage(page + 1)"
                            >
                                Suivant
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <Teleport to="body">
                <div
                    v-if="menuEnv"
                    class="env-menu-panel"
                    role="menu"
                    :style="menuStyle"
                    @click.stop
                >
                    <button
                        type="button"
                        class="env-menu-item"
                        role="menuitem"
                        @click="configureEnvironment(menuEnv)"
                    >
                        Configurer
                    </button>
                    <RouterLink
                        v-if="isSuperAdmin"
                        :to="{ name: 'environments.edit', params: { id: menuEnv.id } }"
                        class="env-menu-item"
                        role="menuitem"
                        @click="closeMenu"
                    >
                        Modifier
                    </RouterLink>
                    <button
                        v-if="isSuperAdmin"
                        type="button"
                        class="env-menu-item"
                        role="menuitem"
                        @click="duplicateEnvironment(menuEnv)"
                    >
                        Dupliquer
                    </button>
                    <button
                        type="button"
                        class="env-menu-item danger"
                        role="menuitem"
                        @click="removeEnvironment(menuEnv)"
                    >
                        Supprimer
                    </button>
                </div>
            </Teleport>
        </template>

        <!-- Détail environnement : entités uniquement -->
        <template v-else>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <button
                        v-if="showEnvironmentsList || isSuperAdmin || canBrowseEnvironments"
                        type="button"
                        class="mb-2 text-sm text-slate-500 hover:text-slate-800"
                        @click="backToList"
                    >
                        ← Retour à la liste
                    </button>
                    <p class="text-sm text-slate-500">Environnement</p>
                    <h2 class="text-xl font-semibold">{{ environment?.name ?? 'Chargement...' }}</h2>
                </div>
                <div v-if="isSuperAdmin && environment" class="flex gap-2">
                    <RouterLink
                        :to="{
                            name: 'environments.create',
                            query: { duplicate_from: String(environment.id) },
                        }"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    >
                        Dupliquer
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'environments.edit', params: { id: environment.id } }"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    >
                        Modifier
                    </RouterLink>
                </div>
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
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import EnvironmentEntitiesPanel from './EnvironmentEntitiesPanel.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const isSuperAdmin = computed(() => (auth.baseUser?.profile ?? auth.user?.profile) === 'super_admin');
const isPlatformAdmin = computed(() => (auth.baseUser?.profile ?? auth.user?.profile) === 'admin');
const environmentId = computed(() => route.params.id ?? null);
const canBrowseEnvironments = computed(() => isSuperAdmin.value || isPlatformAdmin.value);
const showEnvironmentsList = computed(() => canBrowseEnvironments.value && !environmentId.value);

const environments = ref([]);
const environment = ref(null);
const entities = ref([]);
const listLoading = ref(false);
const detailLoading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(8);
const lastPage = ref(1);
const total = ref(0);
const openMenuId = ref(null);
const menuEnv = ref(null);
const menuStyle = ref({});

let searchTimer = null;

function toggleMenu(env, event) {
    if (openMenuId.value === env.id) {
        closeMenu();
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const openUp = window.innerHeight - rect.bottom < 180;

    menuStyle.value = {
        top: openUp ? `${rect.top - 4}px` : `${rect.bottom + 4}px`,
        right: `${window.innerWidth - rect.right}px`,
        transform: openUp ? 'translateY(-100%)' : 'none',
    };
    openMenuId.value = env.id;
    menuEnv.value = env;
}

function closeMenu() {
    openMenuId.value = null;
    menuEnv.value = null;
}

function onDocumentClick() {
    closeMenu();
}

function onWindowScrollOrResize() {
    closeMenu();
}

const fromItem = computed(() => {
    if (!total.value) return 0;
    return (page.value - 1) * perPage.value + 1;
});

const toItem = computed(() => Math.min(page.value * perPage.value, total.value));

const visiblePages = computed(() => {
    const pages = [];
    const start = Math.max(1, page.value - 2);
    const end = Math.min(lastPage.value, page.value + 2);

    for (let number = start; number <= end; number += 1) {
        pages.push(number);
    }

    return pages;
});

function extractPagination(responseData) {
    const root = responseData ?? {};
    const items = Array.isArray(root.data)
        ? root.data
        : (Array.isArray(root.data?.data) ? root.data.data : []);

    return {
        items,
        page: Number(root.current_page ?? root.data?.current_page ?? 1),
        lastPage: Number(root.last_page ?? root.data?.last_page ?? 1),
        total: Number(root.total ?? root.data?.total ?? items.length),
        perPage: Number(root.per_page ?? root.data?.per_page ?? perPage.value),
    };
}

function entitiesCount(env) {
    if (typeof env.entities_count === 'number') {
        return env.entities_count;
    }

    if (Array.isArray(env.entities)) {
        return env.entities.length;
    }

    if (Array.isArray(env.Entities)) {
        return env.Entities.length;
    }

    return 0;
}

function extractRecord(payload, key) {
    const data = payload?.data ?? payload;
    return data?.[key] ?? data?.[key.charAt(0).toUpperCase() + key.slice(1)] ?? null;
}

async function loadEnvironments() {
    listLoading.value = true;
    try {
        const { data } = await api.get('/environments', {
            params: {
                page: page.value,
                per_page: perPage.value,
                search: search.value.trim() || undefined,
                order_by_asc: 'name',
            },
        });

        const pagination = extractPagination(data);
        environments.value = pagination.items;
        page.value = pagination.page;
        lastPage.value = Math.max(1, pagination.lastPage);
        total.value = pagination.total;
        perPage.value = pagination.perPage || perPage.value;
    } finally {
        listLoading.value = false;
    }
}

function goToPage(nextPage) {
    if (nextPage < 1 || nextPage > lastPage.value || nextPage === page.value) return;
    page.value = nextPage;
    loadEnvironments();
}

function changePerPage() {
    page.value = 1;
    loadEnvironments();
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        page.value = 1;
        loadEnvironments();
    }, 300);
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
    closeMenu();
    router.push({ name: 'environments.detail', params: { id } });
}

function configureEnvironment(env) {
    openEnvironment(env.id);
}

function duplicateEnvironment(env) {
    closeMenu();
    router.push({
        name: 'environments.create',
        query: { duplicate_from: String(env.id) },
    });
}

function backToList() {
    router.push({ name: 'environments' });
}

async function removeEnvironment(env) {
    closeMenu();
    if (!confirm(`Supprimer l'environnement « ${env.name} » ?`)) return;
    await api.delete(`/environments/${env.id}`);
    if (environments.value.length === 1 && page.value > 1) {
        page.value -= 1;
    }
    await loadEnvironments();
}

watch(environmentId, (id) => {
    if (id) {
        loadWorkspace();
    } else if (showEnvironmentsList.value) {
        environment.value = null;
        loadEnvironments();
    }
});

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('scroll', onWindowScrollOrResize, true);
    window.addEventListener('resize', onWindowScrollOrResize);

    if (environmentId.value) {
        loadWorkspace();
    } else if (showEnvironmentsList.value) {
        loadEnvironments();
    }
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('scroll', onWindowScrollOrResize, true);
    window.removeEventListener('resize', onWindowScrollOrResize);
    clearTimeout(searchTimer);
});
</script>

<style scoped>
.env-menu {
    position: relative;
    display: inline-flex;
    justify-content: flex-end;
}

.env-menu-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid transparent;
    border-radius: 0.55rem;
    background: transparent;
    color: #64748b;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.env-menu-trigger:hover,
.env-menu.open .env-menu-trigger {
    border-color: #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
}

.env-menu-panel {
    position: fixed;
    z-index: 80;
    min-width: 10.5rem;
    padding: 0.35rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.env-menu-item {
    display: flex;
    width: 100%;
    align-items: center;
    border: 0;
    border-radius: 0.45rem;
    background: transparent;
    padding: 0.55rem 0.7rem;
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 600;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}

.env-menu-item:hover {
    background: #f8fafc;
}

.env-menu-item.danger {
    color: #b91c1c;
}

.env-menu-item.danger:hover {
    background: #fef2f2;
}
</style>
