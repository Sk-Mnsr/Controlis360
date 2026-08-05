<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-semibold">Historiques</h3>
                <p class="text-sm text-slate-500">Liste des utilisateurs enregistrés</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher..."
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
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
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    @click="loadUsers"
                >
                    Actualiser
                </button>
            </div>
        </div>

        <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Chargement...</div>

        <template v-else>
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nom</th>
                        <th class="px-4 py-3 font-medium">E-mail</th>
                        <th class="px-4 py-3 font-medium">Profil</th>
                        <th v-if="isSuperAdmin" class="px-4 py-3 font-medium">Environnement</th>
                        <th class="px-4 py-3 font-medium">Entité</th>
                        <th class="px-4 py-3 font-medium">Statut</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            {{ user.profile_fr }}
                            <span v-if="user.controle_role_fr" class="text-slate-500">({{ user.controle_role_fr }})</span>
                            <span v-else-if="user.audit_role_fr" class="text-slate-500">({{ user.audit_role_fr }})</span>
                            <span v-else-if="user.gouvernance_it_role_fr" class="text-slate-500">({{ user.gouvernance_it_role_fr }})</span>
                            <span v-else-if="user.metier_role_fr" class="text-slate-500">({{ user.metier_role_fr }})</span>
                        </td>
                        <td v-if="isSuperAdmin" class="px-4 py-3">{{ environmentNames(user) }}</td>
                        <td class="px-4 py-3">{{ entityNames(user) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs"
                                :class="user.activated ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                            >
                                {{ user.activated ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <RouterLink
                                :to="{ name: 'users.edit', params: { id: user.id } }"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                            >
                                Modifier
                            </RouterLink>
                        </td>
                    </tr>
                    <tr v-if="!users.length">
                        <td :colspan="isSuperAdmin ? 7 : 6" class="px-4 py-8 text-center text-slate-500">
                            Aucun utilisateur trouvé
                        </td>
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
                        :disabled="page <= 1 || loading"
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
                        :disabled="loading"
                        @click="goToPage(pageNumber)"
                    >
                        {{ pageNumber }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="page >= lastPage || loading"
                        @click="goToPage(page + 1)"
                    >
                        Suivant
                    </button>
                </div>
            </div>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const users = ref([]);
const search = ref('');
const page = ref(1);
const perPage = ref(8);
const lastPage = ref(1);
const total = ref(0);

let searchTimer = null;

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

function environmentNames(user) {
    const items = user.environments ?? user.Environments ?? [];
    if (items.length) {
        return items.map((environment) => environment.name).join(', ');
    }

    return user.environment?.name ?? user.Environment?.name ?? '—';
}

function entityNames(user) {
    const items = user.entities ?? user.Entities ?? [];
    if (items.length) {
        return items.map((entity) => entity.name).join(', ');
    }

    return user.entity?.name ?? user.Entity?.name ?? '—';
}

async function loadUsers() {
    loading.value = true;

    try {
        const { data } = await api.get('/users', {
            params: {
                page: page.value,
                per_page: perPage.value,
                search: search.value.trim() || undefined,
                order_by_asc: 'name',
            },
        });

        const pagination = extractPagination(data);
        users.value = pagination.items;
        page.value = pagination.page;
        lastPage.value = Math.max(1, pagination.lastPage);
        total.value = pagination.total;
        perPage.value = pagination.perPage || perPage.value;
    } finally {
        loading.value = false;
    }
}

function goToPage(nextPage) {
    if (nextPage < 1 || nextPage > lastPage.value || nextPage === page.value) return;
    page.value = nextPage;
    loadUsers();
}

function changePerPage() {
    page.value = 1;
    loadUsers();
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        page.value = 1;
        loadUsers();
    }, 300);
}

onMounted(loadUsers);

onUnmounted(() => {
    clearTimeout(searchTimer);
});
</script>
