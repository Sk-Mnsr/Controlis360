<template>
    <header class="ench-header">
        <div class="ench-header-inner">
            <div class="ench-header-left">
                <button
                    type="button"
                    class="ench-hamburger"
                    aria-label="Ouvrir le menu Enchères"
                    @click="emit('toggle-menu')"
                >
                    ☰
                </button>
                <RouterLink :to="{ name: 'vente-encheres.client.home' }" class="ench-logo">
                    <span class="ench-logo-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 18h9M7 18V9l8-4 2 3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span class="ench-logo-copy">
                        <span class="ench-logo-text">{{ brand.name }}</span>
                        <span class="ench-logo-tag">Ventes aux enchères au Sénégal</span>
                    </span>
                </RouterLink>
                <nav class="ench-nav" aria-label="Navigation principale">
                    <RouterLink
                        class="ench-nav-link"
                        :class="{ 'is-active': route.name === 'vente-encheres.client.home' }"
                        :to="{ name: 'vente-encheres.client.home' }"
                    >
                        Accueil
                    </RouterLink>
                    <button
                        type="button"
                        class="ench-nav-link ench-nav-drop"
                        :class="{ 'is-active': menuOpen }"
                        :aria-expanded="menuOpen"
                        @click="emit('toggle-menu')"
                    >
                        Enchères
                        <span aria-hidden="true">▾</span>
                    </button>
                </nav>
            </div>

            <form class="ench-search" @submit.prevent="submitSearch">
                <input
                    v-model="searchQuery"
                    type="search"
                    class="ench-search-input"
                    placeholder="Rechercher un article, une catégorie..."
                    aria-label="Rechercher"
                />
                <button type="submit" class="ench-search-btn" aria-label="Rechercher">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2" />
                        <path d="M16 16l4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </form>

            <div class="ench-header-right">
                <RouterLink class="ench-help" :to="{ name: 'vente-encheres.client.help' }">
                    <span class="ench-help-icon" aria-hidden="true">?</span>
                    <span>Aide</span>
                </RouterLink>
                <RouterLink
                    v-if="isLoggedIn"
                    class="ench-login"
                    :to="{ name: 'vente-encheres.client.account' }"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.8" /><path d="M5 19.2c1.4-3 3.8-4.4 7-4.4s5.6 1.4 7 4.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                    Mon compte
                    <span aria-hidden="true">▾</span>
                </RouterLink>
                <button v-else type="button" class="ench-login" @click="clientAuth.show()">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.8" /><path d="M5 19.2c1.4-3 3.8-4.4 7-4.4s5.6 1.4 7 4.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                    Se connecter
                </button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ENCHERES_SN } from '../../config/encheres-sn';
import { useEncheresClientAuthStore } from '../../stores/encheresClientAuth';

defineProps({
    menuOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle-menu']);

const clientAuth = useEncheresClientAuthStore();
const route = useRoute();
const router = useRouter();
const brand = ENCHERES_SN;
const searchQuery = ref('');

const isLoggedIn = computed(() => clientAuth.isLoggedIn);

watch(
    () => route.query.q,
    (q) => {
        searchQuery.value = typeof q === 'string' ? q : '';
    },
    { immediate: true },
);

function submitSearch() {
    router.push({
        name: 'vente-encheres.client.list',
        query: { q: searchQuery.value.trim() || undefined },
    });
}

</script>

<style scoped>
.ench-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: #071b41;
    color: #fff;
    box-shadow: 0 2px 12px rgba(7, 27, 65, 0.28);
}

.ench-header-inner {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 0.65rem 1.15rem;
}

.ench-header-left {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
}

.ench-hamburger {
    display: inline-flex;
    border: 0;
    background: transparent;
    color: #fff;
    font-size: 1.35rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.2rem;
}

.ench-logo {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    text-decoration: none;
    color: #fff;
}

.ench-logo-mark {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.65rem;
    background: linear-gradient(145deg, #f59e0b, #fbbf24);
    color: #0b1f4b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ench-logo-mark svg {
    width: 1.2rem;
    height: 1.2rem;
}

.ench-logo-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.ench-logo-text {
    font-size: 1.05rem;
    font-weight: 800;
}

.ench-logo-tag {
    margin-top: 0.12rem;
    font-size: 0.62rem;
    color: #cbd5e1;
}

.ench-nav {
    display: flex;
    align-items: center;
    gap: 0.15rem;
    margin-left: 0.35rem;
}

.ench-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border: 0;
    background: transparent;
    color: #fff;
    font-family: inherit;
    font-size: 0.92rem;
    font-weight: 700;
    text-decoration: none;
    padding: 0.45rem 0.55rem;
    border-radius: 0.45rem;
    cursor: pointer;
    white-space: nowrap;
}

.ench-nav-link:hover,
.ench-nav-link.is-active,
.ench-nav-link.router-link-exact-active {
    color: #ffb000;
}

.ench-search {
    flex: 1;
    display: flex;
    align-items: center;
    min-width: 8rem;
    background: #fff;
    border-radius: 999px;
    padding: 0.15rem 0.35rem 0.15rem 0.95rem;
}

.ench-search-input {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    color: #0f172a;
    font-size: 0.84rem;
    padding: 0.5rem 0;
    outline: none;
}

.ench-search-btn {
    width: 2.1rem;
    height: 2.1rem;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.ench-search-btn svg {
    width: 1.05rem;
    height: 1.05rem;
}

.ench-header-right {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-shrink: 0;
}

.ench-help {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: #fff;
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 700;
    white-space: nowrap;
}

.ench-help-icon {
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 999px;
    border: 1.5px solid rgba(255, 255, 255, 0.85);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 800;
}

.ench-help.router-link-active,
.ench-help:hover {
    color: #ffb000;
}

.ench-login {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: 0;
    border-radius: 0.55rem;
    background: #ffb000;
    color: #071b41;
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.55rem 0.85rem;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
}

.ench-login svg {
    width: 1.05rem;
    height: 1.05rem;
}

.ench-login:hover {
    background: #e59e00;
}

@media (max-width: 1100px) {
    .ench-logo-tag {
        display: none;
    }

    .ench-header-inner {
        gap: 0.65rem;
        padding: 0.55rem 0.85rem;
    }

    .ench-nav-link,
    .ench-help,
    .ench-login {
        font-size: 0.84rem;
    }
}

@media (max-width: 820px) {
    .ench-header-inner {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        grid-template-areas:
            "ham logo actions"
            "nav nav nav"
            "search search search";
        align-items: center;
        gap: 0.55rem 0.65rem;
    }

    .ench-header-left {
        display: contents;
    }

    .ench-hamburger {
        grid-area: ham;
    }

    .ench-logo {
        grid-area: logo;
        min-width: 0;
    }

    .ench-logo-copy {
        min-width: 0;
    }

    .ench-logo-text {
        font-size: 0.98rem;
    }

    .ench-nav {
        grid-area: nav;
        margin-left: 0;
    }

    .ench-nav-link {
        min-height: 2.5rem;
        padding: 0.4rem 0.7rem;
    }

    .ench-search {
        grid-area: search;
        min-width: 0;
        width: 100%;
    }

    .ench-header-right {
        grid-area: actions;
    }

    .ench-help span:not(.ench-help-icon) {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
    }

    .ench-help {
        position: relative;
        min-width: 2.5rem;
        min-height: 2.5rem;
        justify-content: center;
    }

    .ench-login {
        min-height: 2.5rem;
        padding: 0.45rem 0.7rem;
    }

    .ench-hamburger {
        min-width: 2.5rem;
        min-height: 2.5rem;
        justify-content: center;
    }
}
</style>
