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
                    <RouterLink class="ench-hero-btn" :to="{ name: 'vente-encheres.client.list' }">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 19h10M8 19V8l8-4 2 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Voir les enchères
                    </RouterLink>
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

        <section class="ench-section">
            <header class="ench-section-head">
                <div>
                    <h2>
                        <span class="ench-pin" aria-hidden="true"></span>
                        Enchères en cours
                    </h2>
                    <p>Découvrez nos dernières enchères actives et trouvez le bien qui vous intéresse.</p>
                </div>
                <RouterLink :to="{ name: 'vente-encheres.client.list' }">
                    Voir toutes les enchères →
                </RouterLink>
            </header>

            <p v-if="loading" class="ench-loading">Chargement des enchères…</p>
            <div v-else class="ench-grid">
                <EncheresAuctionCard v-for="item in liveAuctions" :key="item.id" :auction="item" />
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchAuctions } from '../../../services/encheresApi';
import EncheresAuctionCard from '../../../components/vente-encheres/EncheresAuctionCard.vue';

const loading = ref(true);
const auctions = ref([]);

const liveAuctions = computed(() =>
    auctions.value.filter((item) => new Date(item.ends_at).getTime() > Date.now()),
);

onMounted(async () => {
    auctions.value = await fetchAuctions();
    loading.value = false;
});
</script>

<style scoped>
.ench-hero {
    margin: 1rem 1.15rem 0;
    border-radius: 1.15rem;
    overflow: hidden;
    background:
        linear-gradient(100deg, rgba(7, 27, 65, 0.94) 0%, rgba(0, 87, 217, 0.72) 58%, rgba(0, 87, 217, 0.35) 100%),
        url('/images/encheres/land-cruiser.jpg') right center / cover no-repeat;
    color: #fff;
    min-height: 18.5rem;
}

.ench-hero-inner {
    width: 100%;
    padding: 2.6rem 2rem 2.2rem;
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(16rem, 0.7fr);
    gap: 2rem;
    align-items: center;
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
    border-radius: 0.65rem;
    background: #ffb000;
    color: #071b41;
    font-weight: 800;
    text-decoration: none;
    padding: 0.75rem 1.1rem;
}

.ench-hero-btn:hover {
    background: #e59e00;
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
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.15rem;
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

.ench-section-head a {
    color: #0057d9;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.ench-grid {
    display: grid;
    gap: 1.15rem;
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.ench-loading {
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
}
</style>
