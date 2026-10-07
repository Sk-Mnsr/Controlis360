<template>
    <div class="dep">
        <header class="dep-head">
            <h3>{{ isEdit ? 'Modifier un bien' : 'Déposer un bien' }}</h3>
            <p v-if="isEdit">Mettez à jour les informations, les photos et les vidéos de ce lot.</p>
            <p v-else>Création côté Admin — le bien pourra ensuite être mis en enchère et publié sur le front Client.</p>
        </header>

        <form class="dep-form" @submit.prevent="onSubmit">
            <fieldset>
                <legend>Informations générales</legend>
                <div class="dep-grid">
                    <label>
                        Catégorie
                        <select v-model="form.category_id" required class="dep-input">
                            <option value="">—</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.label }}</option>
                        </select>
                    </label>
                    <label>
                        Sous-catégorie
                        <select
                            v-model="form.subcategory"
                            class="dep-input"
                            :disabled="!form.category_id || !subcategoryOptions.length"
                        >
                            <option value="">
                                {{
                                    !form.category_id
                                        ? 'Choisissez d’abord une catégorie'
                                        : subcategoryOptions.length
                                            ? '—'
                                            : 'Aucune sous-catégorie (voir menu Catégories)'
                                }}
                            </option>
                            <option v-for="sub in subcategoryOptions" :key="sub" :value="sub">{{ sub }}</option>
                        </select>
                        <span v-if="form.category_id && !subcategoryOptions.length" class="dep-hint">
                            Ajoutez des sous-catégories dans le menu
                            <RouterLink :to="{ name: 'vente-encheres.admin.categories' }">Catégories</RouterLink>.
                        </span>
                    </label>
                    <label>
                        Titre
                        <input v-model="form.title" required class="dep-input" />
                    </label>
                    <label>
                        Comité
                        <MultiSelectDropdown
                            v-model="form.committee_ids"
                            :options="committeeMembers"
                            placeholder="Sélectionner les membres"
                            empty-text="Aucun utilisateur avec le profil Comité"
                            trigger-class="dep-select-trigger"
                        />
                        <span class="dep-hint">
                            Plusieurs personnes du profil Comité (module Vente aux enchères).
                        </span>
                    </label>
                    <label class="dep-span-2">
                        Description
                        <textarea v-model="form.description" rows="4" class="dep-input" />
                    </label>
                    <label>
                        Prix de départ (FCFA)
                        <input v-model.number="form.starting_price" type="number" min="0" required class="dep-input" />
                    </label>
                    <label>
                        Prix de réserve (FCFA)
                        <input v-model.number="form.reserve_price" type="number" min="0" class="dep-input" />
                    </label>
                    <label>
                        Incrément mini (FCFA)
                        <input v-model.number="form.min_increment" type="number" min="0" class="dep-input" />
                    </label>
                    <label>
                        Type de vendeur
                        <select v-model="form.seller_type" class="dep-input">
                            <option>Admin</option>
                            <option>Banque</option>
                            <option>Entreprise</option>
                            <option>Notaire</option>
                            <option>Liquidation</option>
                            <option>Particulier</option>
                        </select>
                    </label>
                    <label>
                        Début
                        <input v-model="form.starts_at" type="datetime-local" class="dep-input" />
                    </label>
                    <label>
                        Fin
                        <input v-model="form.ends_at" type="datetime-local" required class="dep-input" />
                    </label>
                    <label>
                        Région
                        <select v-model="form.region" class="dep-input">
                            <option value="">—</option>
                            <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </label>
                    <label>
                        Ville / adresse
                        <input v-model="form.address" class="dep-input" />
                    </label>
                    <label>
                        Marque
                        <input v-model="form.brand" class="dep-input" />
                    </label>
                    <label>
                        Modèle
                        <input v-model="form.model" class="dep-input" />
                    </label>
                    <label>
                        Année
                        <input v-model.number="form.year" type="number" class="dep-input" />
                    </label>
                    <label>
                        État
                        <input v-model="form.condition" class="dep-input" />
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Images du bien</legend>
                <p class="dep-images-help">
                    Ajoutez plusieurs photos (URL ou fichier). La première devient l’image principale affichée sur le Client.
                </p>

                <div class="dep-images-list">
                    <div v-for="(url, index) in form.images" :key="index" class="dep-image-row">
                        <div class="dep-image-preview">
                            <EncheresImage v-if="url" :src="url" :alt="`Image ${index + 1}`" />
                            <span v-else class="dep-image-empty">{{ index === 0 ? 'Principale' : `Photo ${index + 1}` }}</span>
                        </div>
                        <div class="dep-image-fields">
                            <label>
                                {{ index === 0 ? 'URL image principale' : `URL image ${index + 1}` }}
                                <input
                                    v-model="form.images[index]"
                                    class="dep-input"
                                    placeholder="https://… ou /images/…"
                                />
                            </label>
                            <div class="dep-image-row-actions">
                                <button
                                    v-if="index > 0"
                                    type="button"
                                    class="dep-mini"
                                    @click="makePrimary(index)"
                                >
                                    Définir comme principale
                                </button>
                                <button
                                    type="button"
                                    class="dep-mini dep-mini--danger"
                                    :disabled="form.images.length === 1 && !form.images[0]"
                                    @click="removeImage(index)"
                                >
                                    Retirer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dep-images-actions">
                    <button type="button" class="dep-btn" @click="addImageSlot">+ Ajouter une image (URL)</button>
                    <label class="dep-btn dep-btn--file">
                        + Importer des fichiers
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            hidden
                            @change="onFilesSelected"
                        />
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Vidéos du bien</legend>
                <p class="dep-images-help">
                    Ajoutez plusieurs vidéos : lien YouTube, lien direct (.mp4) ou fichier. Elles s’affichent sur la fiche Client.
                    Les fichiers volumineux (plus de 8 Mo) doivent passer par une URL.
                </p>

                <div class="dep-images-list">
                    <div v-for="(url, index) in form.videos" :key="`video-${index}`" class="dep-image-row">
                        <div class="dep-image-preview dep-video-preview">
                            <span v-if="!url" class="dep-image-empty">Vidéo {{ index + 1 }}</span>
                            <span v-else class="dep-video-badge">{{ videoKind(url) }}</span>
                        </div>
                        <div class="dep-image-fields">
                            <label>
                                URL vidéo {{ index + 1 }}
                                <input
                                    v-model="form.videos[index]"
                                    class="dep-input"
                                    placeholder="https://youtube.com/… ou https://…/video.mp4"
                                />
                            </label>
                            <div class="dep-image-row-actions">
                                <button
                                    type="button"
                                    class="dep-mini dep-mini--danger"
                                    :disabled="form.videos.length === 1 && !form.videos[0]"
                                    @click="removeVideo(index)"
                                >
                                    Retirer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dep-images-actions">
                    <button type="button" class="dep-btn" @click="addVideoSlot">+ Ajouter une vidéo (URL)</button>
                    <label class="dep-btn dep-btn--file">
                        + Importer des vidéos
                        <input
                            type="file"
                            accept="video/*"
                            multiple
                            hidden
                            @change="onVideoFilesSelected"
                        />
                    </label>
                </div>
            </fieldset>

            <label class="dep-check">
                <input v-model="form.publish_now" type="checkbox" />
                Publier immédiatement sur le front Client
            </label>

            <p v-if="message" class="dep-msg">{{ message }}</p>
            <p v-if="error" class="dep-err">{{ error }}</p>

            <div class="dep-actions">
                <button type="submit" class="dep-btn dep-btn--primary">
                    {{ submitLabel }}
                </button>
                <RouterLink :to="{ name: 'vente-encheres.admin.auctions' }" class="dep-btn">
                    Voir les enchères
                </RouterLink>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../../api/client';
import MultiSelectDropdown from '../../../components/MultiSelectDropdown.vue';
import EncheresImage from '../../../components/vente-encheres/EncheresImage.vue';
import { issueCommitteeCode, syncCommitteeLot } from '../../../services/encheresApi';
import { useEncheresAdminStore } from '../../../stores/encheresAdmin';

const store = useEncheresAdminStore();
const route = useRoute();
const router = useRouter();
const message = ref('');
const error = ref('');
const hydrating = ref(false);
const editingId = computed(() => (route.params.id ? String(route.params.id) : ''));
const isEdit = computed(() => Boolean(editingId.value));
const submitLabel = computed(() => {
    if (isEdit.value) {
        return 'Enregistrer les modifications';
    }
    return form.publish_now ? 'Créer et publier' : 'Enregistrer en brouillon';
});

const categories = computed(() => store.categories);
const regions = computed(() => store.regions);
const committeeMembers = ref([]);

const form = reactive({
    category_id: '',
    subcategory: '',
    title: '',
    description: '',
    starting_price: '',
    reserve_price: '',
    min_increment: 100000,
    seller_type: 'Admin',
    starts_at: '',
    ends_at: '',
    region: '',
    address: '',
    brand: '',
    model: '',
    year: '',
    condition: '',
    images: [''],
    videos: [''],
    committee_ids: [],
    publish_now: false,
});

const subcategoryOptions = computed(() => {
    const cat = categories.value.find((item) => item.id === form.category_id);
    if (!cat) return [];
    if (cat.subcategories?.length) return cat.subcategories;
    const fromColumns = (cat.columns || []).flatMap((col) => col.links || []);
    return [...new Set(fromColumns)];
});

watch(() => form.category_id, () => {
    if (hydrating.value) {
        return;
    }
    form.subcategory = '';
});

function toDatetimeLocal(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (value) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function sameIds(left, right) {
    const normalize = (ids) => [...ids].map((id) => Number(id)).sort((a, b) => a - b).join(',');
    return normalize(left) === normalize(right);
}

async function hydrateForm() {
    if (!editingId.value) {
        return;
    }

    const auction = store.getAuctionById(editingId.value);
    if (!auction) {
        error.value = 'Bien introuvable.';
        return;
    }

    hydrating.value = true;
    form.category_id = auction.category_id || '';
    form.subcategory = auction.subcategory || '';
    form.title = auction.title || '';
    form.description = auction.description || '';
    form.starting_price = auction.starting_price ?? '';
    form.reserve_price = auction.reserve_price ?? '';
    form.min_increment = auction.min_increment ?? 100000;
    form.seller_type = auction.seller_type || 'Admin';
    form.starts_at = toDatetimeLocal(auction.starts_at);
    form.ends_at = toDatetimeLocal(auction.ends_at);
    form.region = auction.region || '';
    form.address = auction.address || auction.location || '';
    form.brand = auction.brand || '';
    form.model = auction.model || '';
    form.year = auction.year || '';
    form.condition = auction.condition || '';
    form.images = auction.images?.length ? [...auction.images] : [auction.image || ''];
    form.videos = auction.videos?.length ? [...auction.videos] : [''];
    form.committee_ids = (auction.committee_ids || []).map((id) => Number(id));
    form.publish_now = auction.admin_status === 'published';
    await nextTick();
    hydrating.value = false;
}

function unwrapUsers(payload) {
    const root = payload?.data ?? payload;
    if (Array.isArray(root)) return root;
    if (Array.isArray(root?.data)) return root.data;
    return [];
}

function isEncheresComite(user) {
    const moduleProfile = user?.module_profiles?.['vente-encheres']?.profile;
    return moduleProfile === 'comite' || user?.profile === 'comite';
}

async function loadCommitteeMembers() {
    try {
        const { data } = await api.get('/users', {
            params: {
                paginate: 'false',
                order_by_asc: 'name',
                activated: 1,
            },
        });
        committeeMembers.value = unwrapUsers(data)
            .filter(isEncheresComite)
            .map((user) => ({
                id: Number(user.id),
                name: user.name || user.email,
            }));
    } catch {
        committeeMembers.value = [];
    }
}

onMounted(async () => {
    await loadCommitteeMembers();
    await hydrateForm();
});

watch(editingId, () => {
    hydrateForm();
});

function addImageSlot() {
    form.images.push('');
}

function removeImage(index) {
    if (form.images.length === 1) {
        form.images[0] = '';
        return;
    }
    form.images.splice(index, 1);
}

function makePrimary(index) {
    if (index <= 0 || index >= form.images.length) return;
    const [item] = form.images.splice(index, 1);
    form.images.unshift(item);
}

function onFilesSelected(event) {
    const files = Array.from(event.target.files || []);
    event.target.value = '';
    if (!files.length) return;

    files.forEach((file) => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = () => {
            const dataUrl = String(reader.result || '');
            if (!dataUrl) return;
            const emptyIndex = form.images.findIndex((item) => !String(item || '').trim());
            if (emptyIndex >= 0) {
                form.images[emptyIndex] = dataUrl;
            } else {
                form.images.push(dataUrl);
            }
        };
        reader.readAsDataURL(file);
    });
}

const MAX_VIDEO_BYTES = 8 * 1024 * 1024;

function addVideoSlot() {
    form.videos.push('');
}

function removeVideo(index) {
    if (form.videos.length === 1) {
        form.videos[0] = '';
        return;
    }
    form.videos.splice(index, 1);
}

function videoKind(url) {
    const value = String(url || '');
    if (/youtu\.?be/.test(value)) return 'YouTube';
    if (value.startsWith('data:video')) return 'Fichier';
    return 'Vidéo';
}

function onVideoFilesSelected(event) {
    const files = Array.from(event.target.files || []);
    event.target.value = '';
    error.value = '';
    if (!files.length) return;

    files.forEach((file) => {
        if (!file.type.startsWith('video/')) return;
        if (file.size > MAX_VIDEO_BYTES) {
            error.value = `« ${file.name} » dépasse 8 Mo. Utilisez une URL (YouTube ou lien .mp4).`;
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            const dataUrl = String(reader.result || '');
            if (!dataUrl) return;
            const emptyIndex = form.videos.findIndex((item) => !String(item || '').trim());
            if (emptyIndex >= 0) {
                form.videos[emptyIndex] = dataUrl;
            } else {
                form.videos.push(dataUrl);
            }
        };
        reader.readAsDataURL(file);
    });
}

async function onSubmit() {
    message.value = '';
    error.value = '';

    if (!form.category_id || !form.title || !form.starting_price || !form.ends_at) {
        error.value = 'Catégorie, titre, prix de départ et date de fin sont obligatoires.';
        return;
    }

    const images = form.images.map((item) => String(item || '').trim()).filter(Boolean);
    const videos = form.videos.map((item) => String(item || '').trim()).filter(Boolean);
    const committeeIds = form.committee_ids.map((id) => Number(id));
    const committee = committeeMembers.value
        .filter((member) => committeeIds.includes(member.id))
        .map((member) => ({ id: member.id, name: member.name }));

    const payload = {
        ...form,
        image: images[0] || '',
        images,
        videos,
        committee_ids: committeeIds,
        committee,
        location: form.address || form.region,
        city: form.address,
    };

    if (isEdit.value) {
        const existing = store.getAuctionById(editingId.value);
        if (!existing) {
            error.value = 'Bien introuvable.';
            return;
        }

        const updated = store.updateAuction(editingId.value, {
            ...payload,
            starting_price: Number(form.starting_price) || 0,
            reserve_price: form.reserve_price === '' || form.reserve_price == null ? null : Number(form.reserve_price),
            min_increment: Number(form.min_increment) || 100000,
            current_price: existing.bid_count ? existing.current_price : Number(form.starting_price) || 0,
            admin_status: form.publish_now ? 'published' : 'draft',
            published: form.publish_now,
            status: form.publish_now ? 'live' : 'pending',
        });

        message.value = `Modifications enregistrées : ${updated?.lot || existing.lot}.`;

        const previousIds = existing.committee_ids || [];
        if (committeeIds.length && !sameIds(previousIds, committeeIds)) {
            try {
                const result = await issueCommitteeCode({
                    auction_key: existing.id,
                    lot: updated?.lot || existing.lot,
                    title: updated?.title || existing.title,
                    committee_ids: committeeIds,
                    bids: existing.bids || [],
                });
                const failed = result.failed?.length ? ` Échec pour : ${result.failed.join(', ')}.` : '';
                message.value += ` Un code personnel a été envoyé à ${result.sent} membre(s) du comité.${failed}`;
            } catch (err) {
                error.value = err.response?.data?.message
                    || 'Le bien est enregistré, mais l’e-mail du code comité n’a pas pu être envoyé.';
            }
        } else {
            try {
                await syncCommitteeLot({
                    auction_key: existing.id,
                    lot: updated?.lot || existing.lot,
                    title: updated?.title || existing.title,
                    bids: existing.bids || [],
                });
            } catch (err) {
                error.value = err.response?.data?.message
                    || 'Le bien est enregistré localement, mais l’admin ne pourra pas le voir depuis un autre poste.';
            }
        }

        if (!error.value) {
            router.push({ name: 'vente-encheres.admin.auctions.show', params: { id: existing.id } });
        }
        return;
    }

    const created = store.createAuction(payload);

    message.value = form.publish_now
        ? `Bien publié : ${created.lot}`
        : `Brouillon créé : ${created.lot}. Publiez-le dans « Mettre en enchère ».`;

    if (committeeIds.length) {
        try {
            const result = await issueCommitteeCode({
                auction_key: created.id,
                lot: created.lot,
                title: created.title,
                committee_ids: committeeIds,
                bids: created.bids || [],
            });
            const failed = result.failed?.length ? ` Échec pour : ${result.failed.join(', ')}.` : '';
            message.value += ` Un code personnel a été envoyé à ${result.sent} membre(s) du comité.${failed}`;
        } catch (err) {
            error.value = err.response?.data?.message
                || 'Le bien est enregistré, mais l’e-mail du code comité n’a pas pu être envoyé.';
        }
    } else {
        try {
            await syncCommitteeLot({
                auction_key: created.id,
                lot: created.lot,
                title: created.title,
                bids: created.bids || [],
            });
        } catch (err) {
            error.value = err.response?.data?.message
                || 'Le bien est enregistré localement, mais l’admin ne pourra pas le voir depuis un autre poste.';
        }
    }

    if (form.publish_now && !error.value) {
        setTimeout(() => router.push({ name: 'vente-encheres.admin.auctions' }), 800);
    }
}
</script>

<style scoped>
.dep-head h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
}

.dep-head p {
    margin: 0.35rem 0 1rem;
    color: #64748b;
    font-size: 0.875rem;
}

.dep-form fieldset {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #fff;
    padding: 1.1rem;
    margin: 0 0 1rem;
}

.dep-form legend {
    font-weight: 800;
    font-size: 0.875rem;
    padding: 0 0.35rem;
}

.dep-grid {
    display: grid;
    gap: 0.85rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.dep-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.dep-span-2 {
    grid-column: span 2;
}

.dep-hint {
    font-size: 0.75rem;
    font-weight: 500;
    color: #b45309;
}

.dep-hint a {
    color: #2563eb;
    font-weight: 700;
}

.dep-images-help {
    margin: 0 0 0.85rem;
    color: #64748b;
    font-size: 0.8125rem;
}

.dep-images-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.dep-image-row {
    display: grid;
    grid-template-columns: 96px 1fr;
    gap: 0.75rem;
    align-items: start;
}

.dep-image-preview {
    width: 96px;
    height: 72px;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dep-image-preview :deep(img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.dep-video-preview {
    background: #0f172a;
}

.dep-video-badge {
    font-size: 0.6875rem;
    font-weight: 800;
    color: #f59e0b;
    text-align: center;
}

.dep-image-empty {
    font-size: 0.6875rem;
    color: #94a3b8;
    text-align: center;
    padding: 0.25rem;
}

.dep-image-fields {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.dep-image-fields label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.dep-image-row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.dep-images-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.85rem;
}

.dep-mini {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    border-radius: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.35rem 0.55rem;
    cursor: pointer;
}

.dep-mini:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.dep-mini--danger {
    color: #b91c1c;
    border-color: #fecaca;
}

.dep-btn--file {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}

.dep-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.875rem;
}

:deep(.dep-select-trigger) {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.875rem;
    font-weight: 500;
    background: #fff;
}

.dep-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.85rem;
}

.dep-msg {
    color: #047857;
    font-size: 0.875rem;
    font-weight: 600;
}

.dep-err {
    color: #b91c1c;
    font-size: 0.875rem;
    font-weight: 600;
}

.dep-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.dep-btn {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.875rem;
    padding: 0.65rem 1rem;
    cursor: pointer;
}

.dep-btn--primary {
    border: 0;
    background: #2563eb;
    color: #fff;
}

@media (max-width: 640px) {
    .dep-grid {
        grid-template-columns: 1fr;
    }

    .dep-span-2 {
        grid-column: span 1;
    }
}
</style>
