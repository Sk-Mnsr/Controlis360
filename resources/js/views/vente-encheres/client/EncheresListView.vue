<template>
    <div>
        <section class="ench-hero">
            <div class="ench-hero-inner">
                <div class="ench-hero-copy">
                    <p class="ench-hero-kicker">Plateforme d'enchères — Sénégal &amp; Afrique</p>
                    <h1>Enchérissez. Achetez. Gagnez.</h1>
                    <p class="ench-hero-lead">
                        Découvrez des véhicules, maisons, terrains, motos et biens professionnels aux enchères.
                    </p>
                    <a class="ench-hero-btn" href="#encheres-en-cours">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 19h10M8 19V8l8-4 2 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Voir les enchères
                    </a>
                </div>
                <ul class="ench-trust">
                    <li>
                        <span class="ench-trust-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 9 4.2-1.8 7-4.8 7-9V6l-7-3z" fill="none" stroke="currentColor" stroke-width="1.7" /></svg>
                        </span>
                        <span>
                            <strong>Enchères sécurisées</strong>
                            Vos transactions sont protégées
                        </span>
                    </li>
                    <li>
                        <span class="ench-trust-icon">
                            <svg viewBox="0 0 24 24"><path d="M13 3 6 14h6l-1 7 7-12h-6l1-6z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>
                        </span>
                        <span>
                            <strong>100% transparent</strong>
                            Des offres en toute confiance
                        </span>
                    </li>
                    <li>
                        <span class="ench-trust-icon">
                            <svg viewBox="0 0 24 24"><path d="M5 16v-1a7 7 0 0 1 14 0v1" fill="none" stroke="currentColor" stroke-width="1.7" /><path d="M5 16h3v4H5zm11 0h3v4h-3z" fill="none" stroke="currentColor" stroke-width="1.7" /></svg>
                        </span>
                        <span>
                            <strong>Support client</strong>
                            À votre écoute 7j/7
                        </span>
                    </li>
                </ul>
            </div>
        </section>

        <section id="encheres-en-cours" class="ench-section">
            <header class="ench-section-head">
                <div>
                    <h2>
                        <span class="ench-pin" aria-hidden="true"></span>
                        Enchères en cours
                    </h2>
                    <p>Découvrez nos dernières enchères actives et trouvez le bien qui vous intéresse.</p>
                </div>
            </header>

            <EncheresFiltersBar :model-value="filters" @apply="applyFilters" />

            <p v-if="loading" class="ench-loading">Chargement…</p>
            <p v-else-if="!auctions.length" class="ench-empty">Aucune enchère ne correspond à votre recherche.</p>
            <div v-else class="ench-grid">
                <EncheresAuctionCard v-for="item in auctions" :key="item.id" :auction="item" />
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchAuctions } from '../../../services/encheresApi';
import EncheresAuctionCard from '../../../components/vente-encheres/EncheresAuctionCard.vue';
import EncheresFiltersBar from '../../../components/vente-encheres/EncheresFiltersBar.vue';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const auctions = ref([]);
const filters = reactive({});

async function load() {
    loading.value = true;
    auctions.value = await fetchAuctions({ ...route.query });
    loading.value = false;
}

function applyFilters(next) {
    router.push({
        name: 'vente-encheres.client.list',
        query: {
            category_id: next.category_id || undefined,
            region: next.region || undefined,
            min_price: next.min_price || undefined,
            max_price: next.max_price || undefined,
            q: route.query.q,
        },
    });
}

watch(() => route.query, () => {
    Object.assign(filters, route.query);
    load();
}, { immediate: true, deep: true });

onMounted(() => {
    Object.assign(filters, route.query);
});
</script>

<style scoped>
.ench-hero {
    margin: 1rem 1.15rem 0;
    border-radius: 1.15rem;
    overflow: hidden;
    min-height: 18.5rem;
    background:
        linear-gradient(100deg, rgba(7, 27, 65, 0.94) 0%, rgba(0, 87, 217, 0.72) 58%, rgba(0, 87, 217, 0.35) 100%),
        url('/images/encheres/land-cruiser.jpg') right center / cover no-repeat;
    color: #fff;
}

.ench-hero-inner {
    width: 100%;
    padding: 2.6rem 2rem 2.2rem;
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(16rem, 0.8fr);
    gap: 2rem;
    align-items: end;
}

.ench-hero-kicker {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #93c5fd;
}

.ench-hero h1 {
    margin: 0.45rem 0 0;
    font-size: clamp(1.8rem, 3vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}

.ench-hero-lead {
    max-width: 36rem;
    margin: 0.75rem 0 0;
    color: #dbeafe;
    line-height: 1.5;
}

.ench-hero-btn {
    margin-top: 1.35rem;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border-radius: 0.6rem;
    background: #f59e0b;
    color: #0f172a;
    font-weight: 800;
    text-decoration: none;
    padding: 0.7rem 1.05rem;
}

.ench-hero-btn svg {
    width: 1rem;
    height: 1rem;
}

.ench-trust {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.ench-trust li {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    font-size: 0.8rem;
    color: #dbeafe;
}

.ench-trust strong {
    display: block;
    color: #fff;
    font-size: 0.92rem;
}

.ench-trust-icon {
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 999px;
    border: 1.5px solid rgba(255, 255, 255, 0.45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ench-trust-icon svg {
    width: 1.1rem;
    height: 1.1rem;
}

.ench-section {
    width: 100%;
    padding: 1.35rem 1.15rem 2.5rem;
}

.ench-section-head {
    margin-bottom: 1rem;
}

.ench-section-head h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
}

.ench-pin {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 0.2rem;
    background: #2563eb;
    display: inline-block;
}

.ench-section-head p {
    margin: 0.3rem 0 0;
    color: #64748b;
    font-size: 0.9rem;
}

.ench-grid {
    display: grid;
    gap: 1.15rem;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-top: 1rem;
}

.ench-loading,
.ench-empty {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

@media (max-width: 1100px) {
    .ench-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ench-hero-inner {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 680px) {
    .ench-grid {
        grid-template-columns: 1fr;
    }

    .ench-hero {
        margin: 0.75rem 0.75rem 0;
        min-height: 0;
        border-radius: 0.9rem;
    }

    .ench-hero-inner {
        padding: 1.35rem 1rem 1.2rem;
        gap: 1.15rem;
    }

    .ench-hero h1 {
        font-size: 1.65rem;
    }

    .ench-hero-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    .ench-section {
        padding: 1rem 0.75rem 2rem;
    }
}
</style>
