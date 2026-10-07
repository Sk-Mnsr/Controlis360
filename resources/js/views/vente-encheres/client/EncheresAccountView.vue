<template>
    <div v-if="clientAuth.isLoggedIn" class="ench-account">
        <aside class="ench-account-nav">
            <h2>Mon espace</h2>
            <nav>
                <button
                    v-for="item in menu"
                    :key="item.id"
                    type="button"
                    class="ench-account-link"
                    :class="{ active: active === item.id }"
                    @click="active = item.id"
                >
                    {{ item.label }}
                </button>
            </nav>
            <button type="button" class="ench-account-logout" @click="clientAuth.logout()">
                Se déconnecter
            </button>
        </aside>
        <section class="ench-account-content">
            <h1>{{ activeLabel }}</h1>
            <p class="ench-account-intro">
                Espace EnchèreSN — connecté avec <strong>{{ clientAuth.client?.email }}</strong>.
            </p>
            <ul class="ench-account-list">
                <li v-for="n in sampleNotifications" :key="n">{{ n }}</li>
            </ul>
        </section>
    </div>
    <p v-else class="ench-account-guest">Connectez-vous avec votre adresse e-mail pour ouvrir votre compte EnchèreSN.</p>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useEncheresClientAuthStore } from '../../../stores/encheresClientAuth';

const clientAuth = useEncheresClientAuthStore();
const active = ref('dashboard');

const menu = [
    { id: 'dashboard', label: 'Tableau de bord' },
    { id: 'bids', label: 'Mes enchères' },
    { id: 'won', label: 'Mes enchères gagnées' },
    { id: 'lost', label: 'Mes enchères perdues' },
    { id: 'favorites', label: 'Mes favoris' },
    { id: 'alerts', label: 'Mes alertes' },
    { id: 'payments', label: 'Mes paiements' },
    { id: 'documents', label: 'Mes documents' },
    { id: 'profile', label: 'Mon profil' },
    { id: 'settings', label: 'Paramètres' },
];

const activeLabel = computed(() => menu.find((m) => m.id === active.value)?.label ?? 'Tableau de bord');

const sampleNotifications = [
    'Votre enchère est actuellement gagnante sur LOT #2026-001.',
    'L’enchère se termine bientôt sur LOT #2026-003.',
    'Connectez-vous pour recevoir les notifications des nouvelles enchères.',
];

onMounted(() => {
    if (!clientAuth.isLoggedIn) {
        clientAuth.show();
    }
});
</script>

<style scoped>
.ench-account-guest {
    max-width: 36rem;
    margin: 2rem auto;
    color: #475569;
}

.ench-account {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: 220px 1fr;
}

@media (max-width: 768px) {
    .ench-account {
        grid-template-columns: 1fr;
    }
}

.ench-account-logout {
    margin-top: 1rem;
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 0.55rem;
    background: #fff;
    color: #14213d;
    font-weight: 700;
    padding: 0.55rem 0.75rem;
    cursor: pointer;
}

.ench-account-nav {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
}

.ench-account-nav h2 {
    margin: 0 0 0.75rem;
    font-size: 0.875rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
}

.ench-account-nav nav {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.ench-account-link {
    text-align: left;
    border: 0;
    background: none;
    padding: 0.45rem 0.5rem;
    border-radius: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.ench-account-link.active,
.ench-account-link:hover {
    background: #eff6ff;
    color: #2563eb;
}

.ench-account-content {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
}

.ench-account-content h1 {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 800;
}

.ench-account-intro {
    margin: 0.75rem 0 1rem;
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.5;
}

.ench-account-list {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.875rem;
    color: #334155;
    line-height: 1.6;
}
</style>
