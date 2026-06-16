<template>
    <div class="portal-page">
        <header class="portal-header">
            <div>
                <h1 class="portal-title">Controlis360</h1>
                <p class="portal-subtitle">Sélectionnez un module pour continuer</p>
            </div>
            <p class="portal-welcome">
                Bienvenue, <strong>{{ auth.user?.name }}</strong>
            </p>
        </header>

        <div class="portal-grid">
            <article
                v-for="module in modules"
                :key="module.slug"
                class="portal-card"
                :class="{
                    'portal-card-active': module.active,
                    'portal-card-disabled': !module.active,
                }"
            >
                <div class="portal-card-accent" :style="{ background: module.accent }" />

                <div class="portal-card-body">
                    <div class="portal-card-top">
                        <h2 class="portal-card-title">{{ module.name }}</h2>
                        <span v-if="module.comingSoon" class="portal-badge">Bientôt</span>
                    </div>

                    <p class="portal-card-description">{{ module.description }}</p>

                    <button
                        v-if="module.active"
                        type="button"
                        class="portal-card-btn"
                        :style="{ background: module.accent }"
                        @click="openModule(module)"
                    >
                        Ouvrir le module
                    </button>
                    <p v-else class="portal-card-soon">Module en cours de préparation</p>
                </div>
            </article>
        </div>

        <section v-if="canManagePlatform" class="portal-admin">
            <h3 class="portal-admin-title">Administration plateforme</h3>
            <div class="portal-admin-links">
                <RouterLink :to="{ name: 'environments' }" class="portal-admin-link">
                    Environnements
                </RouterLink>
                <RouterLink :to="{ name: 'users.create' }" class="portal-admin-link">
                    Utilisateurs
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { modules } from '../config/modules';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const canManagePlatform = computed(() => ['super_admin', 'admin'].includes(auth.user?.profile));

function openModule(module) {
    if (!module.active || !module.entryRoute) {
        return;
    }

    router.push({ name: module.entryRoute });
}
</script>

<style scoped>
.portal-page {
    max-width: 72rem;
    margin: 0 auto;
}

.portal-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2.5rem;
}

@media (min-width: 768px) {
    .portal-header {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
    }
}

.portal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
}

.portal-subtitle {
    margin-top: 0.35rem;
    font-size: 0.95rem;
    color: #64748b;
}

.portal-welcome {
    font-size: 0.9rem;
    color: #475569;
}

.portal-grid {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .portal-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1100px) {
    .portal-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.portal-card {
    position: relative;
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: transform 0.15s, box-shadow 0.15s;
}

.portal-card-active:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
}

.portal-card-disabled {
    opacity: 0.72;
}

.portal-card-accent {
    height: 4px;
}

.portal-card-body {
    padding: 1.5rem;
}

.portal-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.portal-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
}

.portal-badge {
    flex-shrink: 0;
    border-radius: 9999px;
    background: #f1f5f9;
    padding: 0.2rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
}

.portal-card-description {
    margin-top: 0.75rem;
    min-height: 3.25rem;
    font-size: 0.875rem;
    line-height: 1.55;
    color: #64748b;
}

.portal-card-btn {
    margin-top: 1.25rem;
    width: 100%;
    border: none;
    border-radius: 0.65rem;
    padding: 0.7rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
    transition: opacity 0.15s;
}

.portal-card-btn:hover {
    opacity: 0.92;
}

.portal-card-soon {
    margin-top: 1.25rem;
    font-size: 0.8125rem;
    font-style: italic;
    color: #94a3b8;
}

.portal-admin {
    margin-top: 2.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1.5rem;
}

.portal-admin-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #94a3b8;
}

.portal-admin-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.85rem;
}

.portal-admin-link {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    padding: 0.55rem 0.9rem;
    font-size: 0.875rem;
    color: #334155;
    transition: background-color 0.15s, color 0.15s;
}

.portal-admin-link:hover {
    background: #f8fafc;
    color: #0f172a;
}
</style>
