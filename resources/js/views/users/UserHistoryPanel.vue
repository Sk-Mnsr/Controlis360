<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-semibold">Historiques</h3>
                <p class="text-sm text-slate-500">Liste des utilisateurs enregistrés</p>
            </div>
            <div class="flex gap-2">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher..."
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-500"
                />
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

        <table v-else class="w-full text-left text-sm">
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
                <tr v-for="user in filteredUsers" :key="user.id" class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                    <td class="px-4 py-3">{{ user.email }}</td>
                    <td class="px-4 py-3">
                        {{ user.profile_fr }}
                        <span v-if="user.controle_role_fr" class="text-slate-500">({{ user.controle_role_fr }})</span>
                        <span v-else-if="user.metier_role_fr" class="text-slate-500">({{ user.metier_role_fr }})</span>
                    </td>
                    <td v-if="isSuperAdmin" class="px-4 py-3">{{ environmentName(user) }}</td>
                    <td class="px-4 py-3">{{ entityName(user) }}</td>
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
                <tr v-if="!filteredUsers.length">
                    <td :colspan="isSuperAdmin ? 7 : 6" class="px-4 py-8 text-center text-slate-500">
                        Aucun utilisateur trouvé
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const users = ref([]);
const search = ref('');

function extractUsers(responseData) {
    const root = responseData?.data ?? responseData;

    if (Array.isArray(root)) {
        return root;
    }

    if (Array.isArray(root?.data)) {
        return root.data;
    }

    return [];
}

function environmentName(user) {
    return user.environment?.name ?? user.Environment?.name ?? '—';
}

function entityName(user) {
    return user.entity?.name ?? user.Entity?.name ?? '—';
}

const filteredUsers = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return users.value;

    return users.value.filter(
        (user) =>
            user.name.toLowerCase().includes(term)
            || user.email.toLowerCase().includes(term)
            || user.profile_fr?.toLowerCase().includes(term)
            || user.controle_role_fr?.toLowerCase().includes(term)
            || user.metier_role_fr?.toLowerCase().includes(term),
    );
});

async function loadUsers() {
    loading.value = true;
    try {
        const params = {};
        if (!isSuperAdmin.value && auth.user?.environment_id) {
            params.environment_id = auth.user.environment_id;
        }

        const { data } = await api.get('/users', {
            params: {
                ...params,
                paginate: 'false',
            },
        });
        users.value = extractUsers(data);
    } finally {
        loading.value = false;
    }
}

onMounted(loadUsers);
</script>
