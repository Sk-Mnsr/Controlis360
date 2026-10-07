<template>
    <article class="ench-card">
        <RouterLink :to="detailTo" class="ench-card-image-wrap">
            <EncheresImage :src="auction.image" :alt="auction.title" class="ench-card-image" />
            <span class="ench-card-badge">{{ statusLabel }}</span>
            <button
                type="button"
                class="ench-card-heart"
                :class="{ 'is-on': liked }"
                :aria-pressed="liked"
                aria-label="Ajouter aux favoris"
                @click.prevent="favorites.toggle(auction.id)"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 20s-7-4.4-7-9.2A3.8 3.8 0 0 1 12 8a3.8 3.8 0 0 1 7 2.8C19 15.6 12 20 12 20z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                </svg>
            </button>
        </RouterLink>

        <div class="ench-card-body">
            <p class="ench-card-lot">{{ lotLabel }}</p>
            <h3 class="ench-card-title">
                <RouterLink :to="detailTo">{{ auction.title }}</RouterLink>
            </h3>
            <p class="ench-card-loc">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                    <circle cx="12" cy="10" r="2.2" fill="currentColor" />
                </svg>
                {{ auction.location }}
            </p>

            <div class="ench-card-price-row">
                <div>
                    <p class="ench-kicker">Prix actuel</p>
                    <p class="ench-price">{{ formatFcfa(auction.current_price) }}</p>
                </div>
                <div class="ench-bids">
                    <p class="ench-kicker">Enchères</p>
                    <p class="ench-bid-count">{{ auction.bid_count ?? 0 }}</p>
                </div>
            </div>

            <div class="ench-card-box">
                <span class="ench-box-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8" />
                        <path d="M12 8v5l3 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                <div>
                    <p class="ench-kicker">Temps restant</p>
                    <p class="ench-box-value">{{ clockLabel }}</p>
                </div>
            </div>

            <div class="ench-card-box">
                <span class="ench-box-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19h10M8 19V8l8-4 2 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <div>
                    <p class="ench-kicker">Prochaine enchère minimum</p>
                    <p class="ench-box-value">{{ formatFcfa(nextMinBid(auction)) }}</p>
                </div>
            </div>

            <RouterLink class="ench-details-btn" :to="detailTo">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 19h10M8 19V8l8-4 2 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Enchérir
            </RouterLink>
        </div>
    </article>
</template>

<script setup>
import { computed, toRef } from 'vue';
import { formatFcfa, nextMinBid } from '../../config/encheres-mock-data';
import { ENCHERES_STATUSES } from '../../config/encheres-sn';
import { useEncheresCountdown } from '../../composables/useEncheresCountdown';
import { useEncheresFavorites } from '../../composables/useEncheresFavorites';
import EncheresImage from './EncheresImage.vue';

const props = defineProps({
    auction: {
        type: Object,
        required: true,
    },
});

const favorites = useEncheresFavorites();
const liked = computed(() => favorites.isFavorite(props.auction.id));
const { countdown } = useEncheresCountdown(toRef(props.auction, 'ends_at'));

const ended = computed(() => countdown.value.ended || new Date(props.auction.ends_at).getTime() <= Date.now());

const statusLabel = computed(() => (ended.value ? ENCHERES_STATUSES.ended : ENCHERES_STATUSES.live));

const lotLabel = computed(() => {
    const lot = String(props.auction.lot || '').trim();
    if (!lot) {
        return '';
    }
    return /^lot\b/i.test(lot) ? lot : `LOT #${lot}`;
});

const detailTo = computed(() => ({
    name: 'vente-encheres.client.detail',
    params: { id: props.auction.id },
}));

const clockLabel = computed(() => {
    const c = countdown.value;
    if (c.ended) {
        return 'Terminée';
    }
    const hours = c.days * 24 + c.hours;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(hours)} h : ${pad(c.minutes)} min : ${pad(c.seconds)} sec`;
});
</script>

<style scoped>
.ench-card {
    display: flex;
    flex-direction: column;
    border-radius: 1rem;
    border: 1px solid #e8eef5;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.ench-card-image-wrap {
    position: relative;
    display: block;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    background: #e2e8f0;
}

.ench-card-image-wrap :deep(img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ench-card-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    border-radius: 0.4rem;
    background: #0057d9;
    color: #fff;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.32rem 0.55rem;
}

.ench-card-heart {
    position: absolute;
    top: 0.65rem;
    right: 0.65rem;
    width: 2rem;
    height: 2rem;
    border: 0;
    border-radius: 999px;
    background: #fff;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
}

.ench-card-heart svg {
    width: 1.05rem;
    height: 1.05rem;
}

.ench-card-heart.is-on {
    color: #ef4444;
}

.ench-card-heart.is-on path {
    fill: currentColor;
}

.ench-card-body {
    padding: 0.95rem 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.ench-card-lot {
    margin: 0;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #94a3b8;
    text-transform: uppercase;
}

.ench-card-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1.3;
}

.ench-card-title a {
    color: #0f172a;
    text-decoration: none;
}

.ench-card-title a:hover {
    color: #2563eb;
}

.ench-card-loc {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.ench-card-loc svg {
    width: 0.9rem;
    height: 0.9rem;
    flex-shrink: 0;
}

.ench-card-price-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.15rem;
}

.ench-kicker {
    margin: 0;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
}

.ench-price {
    margin: 0.15rem 0 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.ench-bids {
    text-align: right;
}

.ench-bid-count {
    margin: 0.15rem 0 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.ench-card-box {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    border-radius: 0.75rem;
    background: #eaf3ff;
    padding: 0.65rem 0.75rem;
}

.ench-box-icon {
    width: 1.7rem;
    height: 1.7rem;
    color: #2563eb;
    flex-shrink: 0;
}

.ench-box-icon svg {
    width: 100%;
    height: 100%;
}

.ench-box-value {
    margin: 0.1rem 0 0;
    font-size: 0.92rem;
    font-weight: 800;
    color: #0f172a;
}

.ench-details-btn {
    margin-top: 0.2rem;
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border-radius: 0.7rem;
    background: #ffb000;
    color: #071b41;
    font-size: 0.92rem;
    font-weight: 800;
    text-decoration: none;
    padding: 0.75rem 1rem;
    min-height: 2.75rem;
}

.ench-details-btn svg {
    width: 1rem;
    height: 1rem;
}

.ench-details-btn:hover {
    background: #e59e00;
}
</style>
