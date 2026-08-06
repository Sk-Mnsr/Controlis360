<template>
    <div class="users-page space-y-6">
        <header class="users-page-header">
            <div>
                <p class="users-page-kicker">Administration</p>
                <h2 class="users-page-title">Utilisateurs</h2>
                <p class="users-page-subtitle">
                    {{ isSuperAdmin ? 'Gestion globale des comptes' : 'Gestion des comptes de votre environnement' }}
                </p>
            </div>
            <RouterLink
                v-if="showCreateLink"
                :to="{ name: 'users.create' }"
                class="users-page-cta"
            >
                + Nouvel utilisateur
            </RouterLink>
        </header>

        <RouterView />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const route = useRoute();

const isSuperAdmin = computed(() => (auth.baseUser?.profile ?? auth.user?.profile) === 'super_admin');
const showCreateLink = computed(() => route.name === 'users.history');
</script>

<style scoped>
.users-page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.users-page-kicker {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #c00000;
}

.users-page-title {
    margin: 0.25rem 0 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

.users-page-subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
}

.users-page-cta {
    display: inline-flex;
    align-items: center;
    border-radius: 0.65rem;
    background: #c00000;
    padding: 0.65rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    text-decoration: none;
    transition: background 0.15s ease;
}

.users-page-cta:hover {
    background: #9f0000;
}
</style>
