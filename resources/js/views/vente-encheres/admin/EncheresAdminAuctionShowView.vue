<template>
    <div v-if="!auction" class="show-empty">
        <p>Bien introuvable.</p>
        <RouterLink :to="{ name: 'vente-encheres.admin.auctions' }" class="show-btn">Retour à la liste</RouterLink>
    </div>

    <div v-else class="show">
        <header class="show-head">
            <div>
                <p class="show-lot">{{ auction.lot }}</p>
                <h3>{{ auction.title }}</h3>
                <p>{{ auction.category_label }}<span v-if="auction.location"> · {{ auction.location }}</span></p>
            </div>
            <div class="show-actions">
                <RouterLink
                    class="show-btn show-btn--edit"
                    :to="{ name: 'vente-encheres.admin.deposit', params: { id: auction.id } }"
                >
                    Modifier
                </RouterLink>
                <RouterLink :to="{ name: 'vente-encheres.admin.auctions' }" class="show-btn">
                    Retour à la liste
                </RouterLink>
            </div>
        </header>

        <section class="show-card">
            <h4>Photos</h4>
            <div v-if="images.length" class="show-gallery">
                <EncheresImage :src="activeImage" :alt="auction.title" class="show-main" />
                <div v-if="images.length > 1" class="show-thumbs">
                    <button
                        v-for="(img, index) in images"
                        :key="index"
                        type="button"
                        class="show-thumb"
                        :class="{ active: activeImage === img }"
                        @click="activeImage = img"
                    >
                        <EncheresImage :src="img" alt="" />
                    </button>
                </div>
            </div>
            <p v-else class="show-muted">Aucune photo pour ce bien.</p>
        </section>

        <section class="show-card">
            <h4>Vidéos</h4>
            <div v-if="videos.length" class="show-videos">
                <div v-for="(video, index) in videos" :key="index" class="show-video">
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
            <p v-else class="show-muted">Aucune vidéo pour ce bien.</p>
        </section>

        <section class="show-card show-meta">
            <p><strong>Prix actuel :</strong> {{ formatFcfa(auction.current_price) }}</p>
            <p><strong>Fin :</strong> {{ formatDate(auction.ends_at) }}</p>
            <p v-if="auction.description">{{ auction.description }}</p>
        </section>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import EncheresImage from '../../../components/vente-encheres/EncheresImage.vue';
import { formatFcfa } from '../../../config/encheres-mock-data';
import { useEncheresAdminStore } from '../../../stores/encheresAdmin';

const route = useRoute();
const store = useEncheresAdminStore();
const activeImage = ref('');

const auction = computed(() => store.getAuctionById(route.params.id));

const images = computed(() => {
    const item = auction.value;
    if (!item) return [];
    if (item.images?.length) return item.images.filter(Boolean);
    return item.image ? [item.image] : [];
});

const videos = computed(() => (auction.value?.videos || []).filter(Boolean));

watch(images, (list) => {
    activeImage.value = list[0] || '';
}, { immediate: true });

function youtubeEmbed(url) {
    const match = String(url || '').match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/);
    return match ? `https://www.youtube.com/embed/${match[1]}` : '';
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<style scoped>
.show-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.show-lot {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #64748b;
    text-transform: uppercase;
}

.show-head h3 {
    margin: 0.2rem 0 0;
    font-size: 1.25rem;
    font-weight: 800;
}

.show-head p {
    margin: 0.25rem 0 0;
    color: #64748b;
    font-size: 0.875rem;
}

.show-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: flex-start;
}

.show-btn {
    border-radius: 0.45rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    text-decoration: none;
    font-size: 0.8125rem;
    font-weight: 700;
    padding: 0.45rem 0.7rem;
}

.show-btn--edit {
    border-color: #d97706;
    background: #d97706;
    color: #fff;
}

.show-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.85rem;
}

.show-card h4 {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
    font-weight: 800;
}

.show-gallery {
    display: grid;
    gap: 0.65rem;
}

.show-main {
    width: 100%;
    max-height: 420px;
    object-fit: contain;
    background: #0f172a;
    border-radius: 0.6rem;
}

.show-thumbs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.show-thumb {
    border: 2px solid transparent;
    border-radius: 0.4rem;
    padding: 0;
    background: #f8fafc;
    cursor: pointer;
}

.show-thumb.active {
    border-color: #2563eb;
}

.show-thumb img {
    width: 84px;
    height: 56px;
    object-fit: cover;
    display: block;
    border-radius: 0.25rem;
}

.show-videos {
    display: grid;
    gap: 0.85rem;
}

.show-video iframe,
.show-video video {
    width: 100%;
    aspect-ratio: 16 / 9;
    border: 0;
    border-radius: 0.6rem;
    background: #0f172a;
}

.show-muted,
.show-empty {
    color: #64748b;
    font-size: 0.875rem;
}

.show-meta p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #334155;
}
</style>
