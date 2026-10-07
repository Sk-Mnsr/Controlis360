import api from '../api/client';
import { useEncheresAdminStore } from '../stores/encheresAdmin';

const USE_MOCK = true;

function normalizeList(payload) {
    const data = payload?.data ?? payload;
    return Array.isArray(data) ? data : (data?.auctions ?? []);
}

export async function fetchAuctions(params = {}) {
    const store = useEncheresAdminStore();
    await store.ensureSynced();

    try {
        const { data } = await api.get('/vente-encheres/auctions', { params });
        const list = normalizeList(data);
        if (Array.isArray(list) && list.length) {
            store.mergePublished(list);
            return list;
        }
    } catch {
        // Le catalogue local reste disponible si l'API ne répond pas.
    }

    return store.listPublicAuctions(params);
}

export async function fetchAuctionById(id) {
    const store = useEncheresAdminStore();

    try {
        const { data } = await api.get(`/vente-encheres/auctions/${id}`);
        const auction = data?.data ?? data ?? null;
        if (auction?.id) {
            store.mergePublished([auction]);
            return auction;
        }
    } catch {
        //
    }

    return store.getAuctionById(id);
}

export async function issueCommitteeCode(payload) {
    const { data } = await api.post('/vente-encheres/comite/codes', payload);
    return data?.data ?? data;
}

export async function syncCommitteeLot(payload) {
    const { data } = await api.post('/vente-encheres/comite/biens', payload);
    return data?.data ?? data;
}

export async function fetchCommitteeAuctions() {
    const { data } = await api.get('/vente-encheres/comite/biens');
    return data?.data ?? [];
}

export async function verifyCommitteeCode(auctionKey, code) {
    const { data } = await api.post('/vente-encheres/comite/verifier', {
        auction_key: auctionKey,
        code,
    });
    return data?.data ?? data;
}

export async function syncCommitteeBid(auctionKey, amount, userRef, email = '') {
    await api.post('/vente-encheres/comite/offres', {
        auction_key: auctionKey,
        amount,
        user_ref: userRef,
        email: email || undefined,
    });
}

export async function sendWinnerMessage(auctionKey, bidId) {
    const { data } = await api.post('/vente-encheres/comite/message-gagnant', {
        auction_key: auctionKey,
        bid_id: bidId,
    });
    return data?.data ?? data;
}

export async function fetchWinnerMessage() {
    const { data } = await api.get('/vente-encheres/comite/message-gagnant');
    return data?.data ?? data;
}

export async function saveWinnerMessage(subject, body) {
    const { data } = await api.put('/vente-encheres/comite/message-gagnant', { subject, body });
    return data?.data ?? data;
}

export async function placeBid(auctionId, amount, userRef = 'Client', email = '') {
    const store = useEncheresAdminStore();
    const auction = store.getAuctionById(auctionId);

    if (!auction) {
        throw new Error('Enchère introuvable');
    }
    if (new Date(auction.ends_at).getTime() <= Date.now()) {
        throw new Error('Enchère terminée');
    }

    const min = (auction.current_price || auction.starting_price) + (auction.min_increment || 100000);
    if (amount < min) {
        throw new Error('Montant insuffisant');
    }

    if (USE_MOCK) {
        auction.bids = auction.bids || [];
        auction.bids.forEach((bid) => {
            if (bid.status === 'Gagnante') {
                bid.status = 'Dépassée';
            }
        });
        auction.current_price = amount;
        auction.bid_count = (auction.bid_count || 0) + 1;
        auction.bids.unshift({
            id: Date.now(),
            user_ref: userRef,
            amount,
            at: new Date().toISOString(),
            status: 'Gagnante',
        });
        store.updateAuction(auctionId, {
            current_price: auction.current_price,
            bid_count: auction.bid_count,
            bids: auction.bids,
        });

        try {
            await syncCommitteeBid(auctionId, amount, userRef, email);
        } catch {
            // L'enchère locale reste enregistrée même si la synchro comité échoue.
        }

        return { auction: store.getAuctionById(auctionId), message: 'Enchère enregistrée' };
    }

    const { data } = await api.post(`/vente-encheres/auctions/${auctionId}/bids`, { amount });
    return data?.data ?? data;
}
