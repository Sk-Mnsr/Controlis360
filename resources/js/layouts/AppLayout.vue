<template>
    <div class="flex min-h-screen bg-slate-50 text-slate-900">
        <aside class="flex w-64 shrink-0 flex-col border-r border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-5">
                <img
                    :src="logoUrl"
                    alt="COFINA — Compagnie Financière Africaine"
                    class="h-9 w-auto max-w-full object-contain object-left"
                />
                <h1 class="mt-3 text-base font-semibold leading-tight text-slate-900">Controlis360</h1>
                <p class="mt-0.5 text-xs text-slate-500">Cartographie des risques</p>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <RouterLink class="nav-link" :to="{ name: 'home' }" active-class="nav-link-active">
                    Home
                </RouterLink>

                <RouterLink
                    v-if="auth.user?.profile === 'super_admin'"
                    class="nav-link"
                    :class="{ 'nav-link-active': isEnvironmentsSection }"
                    :to="{ name: 'environments' }"
                >
                    Environnements
                </RouterLink>
                <RouterLink
                    v-else-if="auth.user?.profile === 'admin' && auth.user?.environment_id"
                    class="nav-link"
                    :to="{ name: 'environments.detail', params: { id: auth.user.environment_id } }"
                    active-class="nav-link-active"
                >
                    Mon environnement
                </RouterLink>

                <div v-if="canManageUsers" class="nav-group">
                    <p class="nav-group-label" :class="{ 'nav-group-label-active': isUsersSection }">
                        Utilisateurs
                    </p>
                    <div class="nav-group-children">
                        <RouterLink
                            class="nav-sublink"
                            :class="{ 'nav-sublink-active': isUsersCreateSection }"
                            :to="{ name: 'users.create' }"
                        >
                            Nouveau
                        </RouterLink>
                        <RouterLink
                            class="nav-sublink"
                            :class="{ 'nav-sublink-active': isUsersHistorySection }"
                            :to="{ name: 'users.history' }"
                        >
                            Historiques
                        </RouterLink>
                    </div>
                </div>

                <RouterLink class="nav-link" :to="{ name: 'referentials' }" active-class="nav-link-active">
                    Référentiels
                </RouterLink>
            </nav>

            <div class="border-t border-slate-200 px-4 py-4">
                <div class="mb-3">
                    <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ userRoleLabel }}</p>
                </div>

                <button
                    type="button"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-100"
                    @click="handleLogout"
                >
                    Déconnexion
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <main class="flex-1 overflow-auto" :class="isHomePage ? '' : 'p-6 lg:p-8'">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const logoUrl = '/logo_Cofina.png';
const isHomePage = computed(() => route.name === 'home' || route.name === 'methodology.show');
const isEnvironmentsSection = computed(() => route.path.startsWith('/environments'));
const isUsersSection = computed(() => route.path.startsWith('/users'));
const isUsersCreateSection = computed(() => route.name === 'users.create');
const isUsersHistorySection = computed(() => route.name === 'users.history' || route.name === 'users.edit');
const canManageUsers = computed(() => ['super_admin', 'admin'].includes(auth.user?.profile));

const userRoleLabel = computed(() => {
    const user = auth.user;
    if (!user) return '';

    if (user.controle_role_fr) {
        return `${user.profile_fr} — ${user.controle_role_fr}`;
    }

    if (user.metier_role_fr) {
        return `${user.profile_fr} — ${user.metier_role_fr}`;
    }

    return user.profile_fr ?? '';
});

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<style scoped>
.nav-link {
    display: block;
    border-radius: 0.5rem;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    color: #475569;
    transition: background-color 0.15s, color 0.15s;
}

.nav-link:hover {
    background-color: #f8fafc;
    color: #0f172a;
}

.nav-link-active {
    background-color: #ecfdf5;
    color: #047857;
    font-weight: 600;
}

.nav-group {
    margin-top: 0.25rem;
}

.nav-group-label {
    padding: 0.5rem 0.75rem 0.35rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
}

.nav-group-label-active {
    color: #047857;
}

.nav-group-children {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    padding-left: 0.5rem;
}

.nav-sublink {
    display: block;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #475569;
    transition: background-color 0.15s, color 0.15s;
}

.nav-sublink:hover {
    background-color: #f8fafc;
    color: #0f172a;
}

.nav-sublink-active {
    background-color: #ecfdf5;
    color: #047857;
    font-weight: 600;
}
</style>
