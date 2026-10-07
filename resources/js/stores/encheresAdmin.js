import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import api from '../api/client';
import { ENCHERES_CATEGORIES, ENCHERES_REGIONS } from '../config/encheres-sn';
import { MOCK_AUCTIONS, auctionImage } from '../config/encheres-mock-data';

const STORAGE_KEY = 'encheresn_admin_v4';

function cloneCategories(source) {
    return source.map((cat) => ({
        id: cat.id,
        label: cat.label,
        icon: cat.icon || '📦',
        active: cat.active !== false,
        sort_order: cat.sort_order ?? 0,
        columns: (cat.columns || []).map((col) => ({
            title: col.title,
            links: [...(col.links || [])],
        })),
        subcategories: [...(cat.subcategories || [])],
    }));
}

function cloneAuctions(source) {
    return source.map((item) => ({
        ...item,
        bids: Array.isArray(item.bids) ? item.bids.map((b) => ({ ...b })) : [],
        images: Array.isArray(item.images) ? [...item.images] : (item.image ? [item.image] : []),
        videos: Array.isArray(item.videos) ? [...item.videos] : [],
        committee: Array.isArray(item.committee) ? item.committee.map((member) => ({ ...member })) : [],
        committee_ids: Array.isArray(item.committee_ids) ? [...item.committee_ids] : [],
        published: item.published !== false,
        admin_status: item.admin_status || (item.status === 'pending' ? 'draft' : 'published'),
    }));
}

function loadState() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return null;
        }
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function persistState(state) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        categories: state.categories,
        auctions: state.auctions,
        regions: state.regions,
    }));
}

export const useEncheresAdminStore = defineStore('encheresAdmin', () => {
    const saved = loadState();

    const categories = ref(cloneCategories(saved?.categories?.length ? saved.categories : ENCHERES_CATEGORIES));
    const auctions = ref(cloneAuctions(saved?.auctions?.length ? saved.auctions : MOCK_AUCTIONS));
    const regions = ref(Array.isArray(saved?.regions) && saved.regions.length ? [...saved.regions] : [...ENCHERES_REGIONS]);

    const activeCategories = computed(() =>
        categories.value
            .filter((cat) => cat.active !== false)
            .slice()
            .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)),
    );

    const publishedAuctions = computed(() =>
        auctions.value.filter((item) => item.published !== false && item.admin_status !== 'draft'),
    );

    const stats = computed(() => {
        const now = Date.now();
        const live = auctions.value.filter((a) => new Date(a.ends_at).getTime() > now && a.admin_status === 'published');
        const ended = auctions.value.filter((a) => new Date(a.ends_at).getTime() <= now);
        const drafts = auctions.value.filter((a) => a.admin_status === 'draft');
        const totalVolume = auctions.value.reduce((sum, a) => sum + (Number(a.current_price) || 0), 0);

        return {
            auctions_total: auctions.value.length,
            auctions_live: live.length,
            auctions_ended: ended.length,
            auctions_draft: drafts.length,
            categories_total: categories.value.length,
            volume_total: totalVolume,
        };
    });

    function save() {
        persistState({
            categories: categories.value,
            auctions: auctions.value,
            regions: regions.value,
        });
        ensureSynced();
    }

    let syncPromise = null;

    function ensureSynced() {
        if (typeof localStorage === 'undefined' || !localStorage.getItem('userToken')) {
            return Promise.resolve();
        }

        if (!syncPromise) {
            syncPromise = api.post('/vente-encheres/auctions/sync', {
                auctions: auctions.value,
            }).catch(() => null).finally(() => {
                syncPromise = null;
            });
        }

        return syncPromise;
    }

    function mergePublished(incoming) {
        if (!Array.isArray(incoming) || !incoming.length) {
            return;
        }

        const byId = new Map(auctions.value.map((item) => [String(item.id), item]));
        incoming.forEach((item) => {
            if (item?.id) {
                byId.set(String(item.id), item);
            }
        });
        auctions.value = [...byId.values()];
        persistState({
            categories: categories.value,
            auctions: auctions.value,
            regions: regions.value,
        });
    }

    function upsertCategory(payload) {
        const id = payload.id || slugify(payload.label);
        const index = categories.value.findIndex((cat) => cat.id === id);
        const next = {
            id,
            label: payload.label?.trim() || 'Sans nom',
            icon: payload.icon || '📦',
            active: payload.active !== false,
            sort_order: Number(payload.sort_order) || 0,
            columns: payload.columns?.length
                ? payload.columns
                : [{ title: 'Sous-catégories', links: [...(payload.subcategories || [])] }],
            subcategories: [...(payload.subcategories || [])],
        };

        if (index >= 0) {
            categories.value[index] = next;
        } else {
            categories.value.push(next);
        }
        save();
        return next;
    }

    function deleteCategory(id) {
        categories.value = categories.value.filter((cat) => cat.id !== id);
        save();
    }

    function createAuction(payload) {
        const id = payload.id || `A-${Date.now()}`;
        const lot = payload.lot || `LOT #${id}`;
        const category = categories.value.find((cat) => cat.id === payload.category_id);
        const auction = {
            id: String(id),
            lot,
            title: payload.title,
            category_id: payload.category_id,
            category_label: category?.label || payload.category_label || 'Divers',
            subcategory: payload.subcategory || '',
            location: payload.location || payload.address || '',
            region: payload.region || '',
            city: payload.city || '',
            starting_price: Number(payload.starting_price) || 0,
            current_price: Number(payload.starting_price) || 0,
            reserve_price: Number(payload.reserve_price) || null,
            min_increment: Number(payload.min_increment) || 100000,
            bid_count: 0,
            status: payload.publish_now ? 'live' : 'pending',
            admin_status: payload.publish_now ? 'published' : 'draft',
            published: Boolean(payload.publish_now),
            ends_at: payload.ends_at ? new Date(payload.ends_at).toISOString() : new Date(Date.now() + 48 * 3600 * 1000).toISOString(),
            starts_at: payload.starts_at ? new Date(payload.starts_at).toISOString() : new Date().toISOString(),
            image: payload.image || auctionImage(`lot-${id}`),
            images: payload.images?.length
                ? payload.images
                : [payload.image || auctionImage(`lot-${id}`)],
            videos: Array.isArray(payload.videos)
                ? payload.videos.map((item) => String(item || '').trim()).filter(Boolean)
                : [],
            committee_ids: Array.isArray(payload.committee_ids) ? payload.committee_ids.map((id) => Number(id)) : [],
            committee: Array.isArray(payload.committee) ? payload.committee.map((member) => ({ ...member })) : [],
            seller_type: payload.seller_type || 'Admin',
            condition: payload.condition || '',
            year: payload.year || null,
            brand: payload.brand || '',
            model: payload.model || '',
            description: payload.description || '',
            address: payload.address || '',
            bids: [],
        };

        auctions.value.unshift(auction);
        save();
        return auction;
    }

    function updateAuction(id, patch) {
        const index = auctions.value.findIndex((item) => String(item.id) === String(id));
        if (index < 0) {
            return null;
        }

        const current = auctions.value[index];
        const category = categories.value.find((cat) => cat.id === (patch.category_id ?? current.category_id));
        const next = {
            ...current,
            ...patch,
            category_label: category?.label || patch.category_label || current.category_label,
        };

        if (patch.publish_now) {
            next.admin_status = 'published';
            next.published = true;
            next.status = 'live';
        }

        auctions.value[index] = next;
        save();
        return next;
    }

    function publishAuction(id) {
        return updateAuction(id, { publish_now: true, admin_status: 'published', published: true, status: 'live' });
    }

    function unpublishAuction(id) {
        return updateAuction(id, { admin_status: 'draft', published: false, status: 'pending', publish_now: false });
    }

    function deleteAuction(id) {
        const auctionId = String(id);
        auctions.value = auctions.value.filter((item) => String(item.id) !== auctionId);
        persistState({
            categories: categories.value,
            auctions: auctions.value,
            regions: regions.value,
        });
        if (typeof localStorage !== 'undefined' && localStorage.getItem('userToken')) {
            api.delete(`/vente-encheres/auctions/${auctionId}`).catch(() => null);
        }
    }

    function getAuctionById(id) {
        return auctions.value.find((item) => String(item.id) === String(id)) ?? null;
    }

    function listPublicAuctions(params = {}) {
        let result = [...publishedAuctions.value];
        const q = String(params.q ?? params.search ?? '').trim().toLowerCase();

        if (q) {
            result = result.filter((item) => (
                item.title?.toLowerCase().includes(q)
                || item.lot?.toLowerCase().includes(q)
                || item.location?.toLowerCase().includes(q)
                || item.brand?.toLowerCase().includes(q)
                || item.model?.toLowerCase().includes(q)
                || item.category_label?.toLowerCase().includes(q)
            ));
        }

        if (params.category_id) {
            result = result.filter((item) => item.category_id === params.category_id);
        }

        if (params.region) {
            result = result.filter((item) => item.region === params.region);
        }

        if (params.min_price) {
            result = result.filter((item) => item.current_price >= Number(params.min_price));
        }

        if (params.max_price) {
            result = result.filter((item) => item.current_price <= Number(params.max_price));
        }

        return result;
    }

    function resetToDefaults() {
        categories.value = cloneCategories(ENCHERES_CATEGORIES);
        auctions.value = cloneAuctions(MOCK_AUCTIONS);
        regions.value = [...ENCHERES_REGIONS];
        save();
    }

    return {
        categories,
        auctions,
        regions,
        activeCategories,
        publishedAuctions,
        stats,
        upsertCategory,
        deleteCategory,
        createAuction,
        updateAuction,
        publishAuction,
        unpublishAuction,
        deleteAuction,
        getAuctionById,
        listPublicAuctions,
        mergePublished,
        ensureSynced,
        resetToDefaults,
        save,
    };
});

function slugify(value) {
    return String(value || 'categorie')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
        || `cat-${Date.now()}`;
}
