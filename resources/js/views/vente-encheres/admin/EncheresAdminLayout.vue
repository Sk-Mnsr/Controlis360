<template>
    <div class="adm-root">
        <aside class="adm-sidebar">
            <div class="adm-brand">
                <p class="adm-brand-kicker">Back-office</p>
                <h1>EnchèreSN Admin</h1>
            </div>

            <nav class="adm-nav">
                <RouterLink class="adm-nav-back" :to="{ name: 'vente-encheres.home' }">← Module</RouterLink>
                <RouterLink
                    v-for="item in menu"
                    :key="item.name"
                    class="adm-nav-link"
                    :class="{ active: isMenuActive(item.name) }"
                    :to="{ name: item.name }"
                >
                    {{ item.label }}
                </RouterLink>
            </nav>

            <div class="adm-user">
                <p class="adm-user-name">{{ auth.user?.name }}</p>
                <p class="adm-user-role">{{ auth.user?.profile_fr }}</p>
            </div>
        </aside>

        <div class="adm-main">
            <header class="adm-topbar">
                <div>
                    <p class="adm-topbar-kicker">Administration</p>
                    <h2>{{ currentTitle }}</h2>
                </div>
                <RouterLink class="adm-topbar-btn" :to="{ name: 'vente-encheres.admin.deposit' }">
                    + Déposer un bien
                </RouterLink>
            </header>
            <main class="adm-content">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../../stores/auth';
import { canAccessVenteEncheresAdmin } from '../../../config/vente-encheres-access';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const menu = [
    { name: 'vente-encheres.admin.dashboard', label: 'Tableau de bord' },
    { name: 'vente-encheres.admin.deposit', label: 'Déposer un bien' },
    { name: 'vente-encheres.admin.auctions', label: 'Mettre en enchère' },
    { name: 'vente-encheres.admin.categories', label: 'Catégories & sous-cat.' },
    { name: 'vente-encheres.admin.settings', label: 'Paramétrage' },
];

const currentTitle = computed(() => {
    if (route.name === 'vente-encheres.admin.auctions.show') {
        return 'Fiche du bien';
    }
    if (route.name === 'vente-encheres.admin.deposit' && route.params.id) {
        return 'Modifier un bien';
    }
    return menu.find((item) => item.name === route.name)?.label ?? 'Admin';
});

function isMenuActive(name) {
    if (route.name === name) {
        return true;
    }
    if (name === 'vente-encheres.admin.auctions' && route.name === 'vente-encheres.admin.auctions.show') {
        return true;
    }
    return false;
}

onMounted(() => {
    if (!canAccessVenteEncheresAdmin(auth.baseUser ?? auth.user)) {
        router.replace({ name: 'vente-encheres.home' });
    }
});
</script>

<style scoped>
.adm-root {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 240px 1fr;
    background: #f1f5f9;
}

.adm-sidebar {
    background: #0f172a;
    color: #f8fafc;
    display: flex;
    flex-direction: column;
    padding: 1.25rem 1rem;
}

.adm-brand-kicker {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f59e0b;
}

.adm-brand h1 {
    margin: 0.25rem 0 0;
    font-size: 1.1rem;
    font-weight: 800;
}

.adm-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: 1.5rem;
    flex: 1;
}

.adm-nav-back,
.adm-nav-link {
    text-decoration: none;
    border-radius: 0.45rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #cbd5e1;
}

.adm-nav-back {
    margin-bottom: 0.5rem;
    color: #94a3b8;
}

.adm-nav-link:hover,
.adm-nav-link.active {
    background: rgba(37, 99, 235, 0.25);
    color: #fff;
}

.adm-user {
    border-top: 1px solid rgba(148, 163, 184, 0.25);
    padding-top: 0.85rem;
}

.adm-user-name {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 700;
}

.adm-user-role {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: #94a3b8;
}

.adm-main {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.adm-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}

.adm-topbar-kicker {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
}

.adm-topbar h2 {
    margin: 0.15rem 0 0;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
}

.adm-topbar-btn {
    border-radius: 0.5rem;
    background: #f59e0b;
    color: #0f172a;
    font-size: 0.8125rem;
    font-weight: 800;
    text-decoration: none;
    padding: 0.55rem 0.9rem;
    white-space: nowrap;
}

.adm-content {
    padding: 1.25rem 1.5rem 2rem;
}

@media (max-width: 900px) {
    .adm-root {
        grid-template-columns: 1fr;
    }

    .adm-sidebar {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .adm-nav {
        flex-direction: row;
        flex-wrap: wrap;
        margin-top: 0;
        flex: 1 1 100%;
    }

    .adm-user {
        display: none;
    }
}
</style>
