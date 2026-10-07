<template>
    <div v-if="loading" class="ench-loading">Chargement…</div>
    <div v-else-if="!auction" class="ench-empty">Enchère introuvable.</div>
    <div v-else class="ench-detail">
        <div class="ench-detail-gallery">
            <EncheresImage :src="activeImage" :alt="auction.title" class="ench-detail-main-img" />
            <div v-if="auction.images?.length > 1" class="ench-detail-thumbs">
                <button
                    v-for="(img, idx) in auction.images"
                    :key="idx"
                    type="button"
                    class="ench-detail-thumb"
                    :class="{ active: activeImage === img }"
                    @click="activeImage = img"
                >
                    <EncheresImage :src="img" alt="" />
                </button>
            </div>
        </div>

        <aside class="ench-detail-panel">
            <p class="ench-detail-lot">{{ auction.lot }}</p>
            <h1>{{ auction.title }}</h1>
            <p class="ench-detail-loc">{{ auction.location }}</p>
            <span class="ench-detail-status">{{ statusLabel }}</span>

            <dl class="ench-detail-stats">
                <div>
                    <dt>Prix de départ</dt>
                    <dd>{{ formatFcfa(auction.starting_price) }}</dd>
                </div>
                <div>
                    <dt>Prix actuel</dt>
                    <dd class="ench-detail-current">{{ formatFcfa(auction.current_price) }}</dd>
                </div>
                <div>
                    <dt>Enchères</dt>
                    <dd>{{ auction.bid_count }}</dd>
                </div>
            </dl>

            <div class="ench-detail-countdown-wrap">
                <p class="ench-detail-countdown-label">Temps restant</p>
                <EncheresCountdown :ends-at="auction.ends_at" />
            </div>

            <p class="ench-detail-min">
                Prochaine enchère minimum :
                <strong>{{ formatFcfa(minBid) }}</strong>
            </p>

            <form class="ench-bid-form" @submit.prevent="onBidClick">
                <label>
                    Votre enchère (FCFA)
                    <input v-model.number="bidAmount" type="number" :min="ended ? minBid : undefined" step="50000" class="ench-bid-input" placeholder="Saisissez votre montant" />
                </label>
                <p v-if="bidError" class="ench-bid-error">{{ bidError }}</p>
                <p v-if="bidSuccess" class="ench-bid-success">{{ bidSuccess }}</p>
                <button type="button" class="ench-bid-btn" :disabled="ended || bidding" @click="onBidClick">
                    {{ bidding ? 'Envoi…' : (isLoggedIn ? 'Placer une enchère' : 'Se connecter pour enchérir') }}
                </button>
            </form>

            <p class="ench-detail-desc">{{ auction.description }}</p>
        </aside>

        <section v-if="auction.videos?.length" class="ench-detail-videos">
            <h2>Vidéos</h2>
            <div class="ench-video-list">
                <div v-for="(video, idx) in auction.videos" :key="idx" class="ench-video-item">
                    <iframe
                        v-if="youtubeEmbed(video)"
                        :src="youtubeEmbed(video)"
                        title="Vidéo du bien"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    />
                    <video v-else :src="video" controls playsinline />
                </div>
            </div>
        </section>

        <section class="ench-detail-history">
            <button type="button" class="ench-history-toggle" @click="historyOpen = !historyOpen">
                <span class="ench-history-heading">
                    <span class="ench-history-lock" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="5" y="11" width="14" height="9" rx="2" />
                            <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span>
                        <h2>Historique des enchères</h2>
                        <p>{{ historyHint }}</p>
                    </span>
                </span>
                <span class="ench-history-chevron" :class="{ open: historyOpen }" aria-hidden="true">⌃</span>
            </button>

            <div v-show="historyOpen">
                <table v-if="visibleBids.length" class="ench-history-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bid in visibleBids" :key="bid.id">
                            <td>{{ bid.user_ref }}</td>
                            <td class="ench-history-amount">{{ formatFcfa(bid.amount) }}</td>
                            <td>{{ formatBidDate(bid.at) }}</td>
                            <td>
                                <span class="ench-history-status" :class="statusClass(bid.status)">
                                    {{ bid.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="ench-empty-inline">{{ historyEmpty }}</p>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { formatFcfa, nextMinBid } from '../../../config/encheres-mock-data';
import { ENCHERES_STATUSES } from '../../../config/encheres-sn';
import { canAccessVenteEncheresAdmin } from '../../../config/vente-encheres-access';
import { useAuthStore } from '../../../stores/auth';
import { fetchAuctionById, placeBid } from '../../../services/encheresApi';
import EncheresCountdown from '../../../components/vente-encheres/EncheresCountdown.vue';
import EncheresImage from '../../../components/vente-encheres/EncheresImage.vue';
import { useEncheresClientAuthStore } from '../../../stores/encheresClientAuth';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
});

const route = useRoute();
const clientAuth = useEncheresClientAuthStore();
const platformAuth = useAuthStore();
const loading = ref(true);
const auction = ref(null);
const activeImage = ref('');
const bidAmount = ref(0);
const bidError = ref('');
const bidSuccess = ref('');
const bidding = ref(false);
const historyOpen = ref(true);

const minBid = computed(() => (auction.value ? nextMinBid(auction.value) : 0));
const isLoggedIn = computed(() => clientAuth.isLoggedIn);
const isAuctionAdmin = computed(() => canAccessVenteEncheresAdmin(platformAuth.user));

const visibleBids = computed(() => {
    const bids = auction.value?.bids ?? [];
    if (isAuctionAdmin.value) {
        return bids;
    }
    if (!isLoggedIn.value) {
        return [];
    }
    const mine = [clientAuth.client?.name, clientAuth.client?.email]
        .filter(Boolean)
        .map((value) => String(value).toLowerCase());
    return bids.filter((bid) => mine.includes(String(bid.user_ref || '').toLowerCase()));
});

const historyHint = computed(() => {
    if (isAuctionAdmin.value) {
        return 'Historique complet des offres, réservé à l’administration.';
    }
    if (isLoggedIn.value) {
        return 'Vous voyez uniquement vos propres enchères. Les offres des autres participants restent privées.';
    }
    return 'Les offres des participants restent privées. Connectez-vous pour voir vos propres enchères.';
});

const historyEmpty = computed(() => {
    if ((auction.value?.bids?.length ?? 0) === 0) {
        return 'Aucune enchère pour le moment.';
    }
    if (!isLoggedIn.value && !isAuctionAdmin.value) {
        return 'Connectez-vous pour consulter vos enchères sur ce bien.';
    }
    return 'Vous n’avez pas encore enchéri sur ce bien.';
});

const ended = computed(() => {
    if (!auction.value?.ends_at) return true;
    return new Date(auction.value.ends_at).getTime() <= Date.now();
});

const statusLabel = computed(() => (ended.value ? ENCHERES_STATUSES.ended : ENCHERES_STATUSES.live));

async function load() {
    loading.value = true;
    const auctionId = props.id || route.params.id;
    auction.value = await fetchAuctionById(auctionId);
    activeImage.value = auction.value?.image ?? '';
    bidAmount.value = '';
    loading.value = false;
}

function onBidClick() {
    if (!isLoggedIn.value) {
        clientAuth.show();
        return;
    }
    submitBid();
}

async function submitBid() {
    if (!auction.value || ended.value || !isLoggedIn.value) return;

    const confirmed = window.confirm(
        `Confirmer votre enchère de ${formatFcfa(bidAmount.value)} ?`,
    );
    if (!confirmed) return;

    bidding.value = true;
    bidError.value = '';
    bidSuccess.value = '';

    try {
        const result = await placeBid(
            auction.value.id,
            bidAmount.value,
            clientAuth.client?.name || clientAuth.client?.email || 'Client',
            clientAuth.client?.email || '',
        );
        auction.value = result.auction ?? auction.value;
        bidAmount.value = '';
        bidSuccess.value = result.message ?? 'Enchère enregistrée.';
    } catch (err) {
        bidError.value = err.message ?? 'Impossible d\'enregistrer l\'enchère.';
    } finally {
        bidding.value = false;
    }
}

function youtubeEmbed(url) {
    const value = String(url || '');
    const match = value.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/);
    return match ? `https://www.youtube.com/embed/${match[1]}` : '';
}

function formatBidDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}

function statusClass(status) {
    const value = String(status || '').toLowerCase();
    if (value.includes('gagn')) return 'is-winning';
    if (value.includes('dépass') || value.includes('depass')) return 'is-outbid';
    return 'is-neutral';
}

watch(() => props.id, load);
onMounted(load);
</script>

<style scoped>
.ench-detail {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr;
}

@media (min-width: 960px) {
    .ench-detail {
        grid-template-columns: 1.2fr 1fr;
    }

    .ench-detail-history,
    .ench-detail-videos {
        grid-column: 1 / -1;
    }
}

.ench-detail-videos h2 {
    margin: 0 0 0.75rem;
    font-size: 1.05rem;
    font-weight: 800;
}

.ench-video-list {
    display: grid;
    gap: 0.85rem;
}

.ench-video-item iframe,
.ench-video-item video {
    width: 100%;
    aspect-ratio: 16 / 9;
    border: 0;
    border-radius: 0.65rem;
    background: #0f172a;
    display: block;
}

.ench-detail-videos {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
}

.ench-detail-gallery {
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.ench-detail-main-img,
.ench-detail-gallery :deep(img.ench-detail-main-img),
.ench-detail-gallery :deep(> img) {
    width: 100%;
    aspect-ratio: 16/10;
    object-fit: cover;
    display: block;
}

.ench-detail-thumbs {
    display: flex;
    gap: 0.5rem;
    padding: 0.65rem;
    overflow-x: auto;
}

.ench-detail-thumb {
    border: 2px solid transparent;
    border-radius: 0.35rem;
    padding: 0;
    cursor: pointer;
    flex-shrink: 0;
    background: none;
}

.ench-detail-thumb.active {
    border-color: #2563eb;
}

.ench-detail-thumb img {
    width: 72px;
    height: 48px;
    object-fit: cover;
    display: block;
    border-radius: 0.25rem;
}

.ench-detail-panel {
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    padding: 1.25rem;
}

.ench-detail-lot {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #64748b;
}

.ench-detail-panel h1 {
    margin: 0.35rem 0 0;
    font-size: 1.35rem;
    font-weight: 800;
}

.ench-detail-loc {
    margin: 0.35rem 0 0;
    color: #64748b;
    font-size: 0.875rem;
}

.ench-detail-status {
    display: inline-block;
    margin-top: 0.65rem;
    padding: 0.25rem 0.55rem;
    border-radius: 0.35rem;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
}

.ench-detail-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin: 1rem 0 0;
}

.ench-detail-stats dt {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #94a3b8;
}

.ench-detail-stats dd {
    margin: 0.2rem 0 0;
    font-weight: 700;
    font-size: 0.875rem;
}

.ench-detail-current {
    color: #b45309;
    font-size: 1rem !important;
}

.ench-detail-countdown-wrap {
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.ench-detail-countdown-label {
    margin: 0 0 0.35rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
}

.ench-detail-min {
    margin: 0.85rem 0 0;
    font-size: 0.8125rem;
    color: #475569;
}

.ench-bid-form {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.ench-bid-form label {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #334155;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.ench-bid-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.55rem 0.75rem;
    font-size: 1rem;
    font-weight: 700;
}

.ench-bid-btn {
    border: 0;
    border-radius: 0.5rem;
    background: #f59e0b;
    color: #0f172a;
    font-weight: 800;
    font-size: 0.9375rem;
    padding: 0.75rem;
    cursor: pointer;
}

.ench-bid-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ench-bid-error {
    margin: 0;
    color: #b91c1c;
    font-size: 0.8125rem;
}

.ench-bid-success {
    margin: 0;
    color: #047857;
    font-size: 0.8125rem;
}

.ench-detail-desc {
    margin: 1rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.5;
}

.ench-detail-history {
    background: #fff;
    border-radius: 0.9rem;
    border: 1px solid #e2e8f0;
    padding: 0.35rem 1.25rem 1rem;
}

.ench-history-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
    border: 0;
    background: transparent;
    padding: 0.9rem 0;
    cursor: pointer;
    text-align: left;
}

.ench-history-heading {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.ench-history-lock {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #2563eb;
    flex-shrink: 0;
}

.ench-history-lock svg {
    width: 1.1rem;
    height: 1.1rem;
}

.ench-detail-history h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.ench-history-heading p {
    margin: 0.2rem 0 0;
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.4;
}

.ench-history-chevron {
    color: #94a3b8;
    font-size: 1.1rem;
    transform: rotate(180deg);
}

.ench-history-chevron.open {
    transform: none;
}

.ench-history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.ench-history-table th,
.ench-history-table td {
    border-bottom: 1px solid #e2e8f0;
    padding: 0.85rem 0.5rem;
    text-align: left;
    color: #334155;
}

.ench-history-table th {
    font-weight: 700;
    color: #94a3b8;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.ench-history-amount {
    font-weight: 800;
    letter-spacing: 0.12em;
    color: #64748b;
}

.ench-history-status {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.2rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.ench-history-status.is-winning {
    background: #dcfce7;
    color: #15803d;
}

.ench-history-status.is-outbid {
    background: #f1f5f9;
    color: #64748b;
}

.ench-history-status.is-neutral {
    background: #eff6ff;
    color: #1d4ed8;
}

.ench-loading,
.ench-empty {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.ench-empty-inline {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
}
</style>
