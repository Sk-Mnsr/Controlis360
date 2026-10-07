<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Espace Comité</p>
            <h2 class="mt-1 text-xl font-semibold text-slate-900">Offres des clients</h2>
            <p class="mt-2 text-sm text-slate-600">
                Bonjour <strong>{{ auth.user?.name }}</strong>.
                <template v-if="isAdmin">
                    Vous voyez tous les biens et les offres des clients, sans code.
                </template>
                <template v-else>
                    Saisissez le code à 3 chiffres reçu par e-mail pour afficher les enchères d’un bien.
                </template>
            </p>
        </section>

        <p v-if="loading" class="text-sm text-slate-500">Chargement…</p>
        <p v-else-if="loadError" class="text-sm font-semibold text-red-700">{{ loadError }}</p>
        <p v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
            {{ isAdmin ? 'Aucun bien déposé pour le moment.' : 'Aucun bien ne vous a été assigné pour le moment.' }}
        </p>

        <section
            v-for="item in items"
            :key="item.auction_key"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ item.lot || item.auction_key }}</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ item.title }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ item.bid_count }} offre(s) enregistrée(s)</p>

            <form v-if="item.requires_code !== false" class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="unlock(item)">
                <label class="text-sm font-semibold text-slate-700">
                    Code à 3 chiffres
                    <input
                        v-model="codes[item.auction_key]"
                        inputmode="numeric"
                        maxlength="3"
                        pattern="[0-9]{3}"
                        class="mt-1 block w-32 rounded-lg border border-slate-300 px-3 py-2"
                        placeholder="000"
                    />
                </label>
                <button
                    type="submit"
                    class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-semibold text-white"
                    :disabled="pendingKey === item.auction_key"
                >
                    {{ pendingKey === item.auction_key ? 'Vérification…' : 'Voir les offres' }}
                </button>
            </form>
            <p v-if="errors[item.auction_key]" class="mt-2 text-sm font-semibold text-red-700">{{ errors[item.auction_key] }}</p>

            <table v-if="visibleBids(item)" class="mt-4 w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                        <th class="py-2">Client</th>
                        <th v-if="!isAdmin">Montant</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="bid in visibleBids(item)" :key="bid.id" class="border-b border-slate-100">
                        <td class="py-2">{{ bid.user_ref }}</td>
                        <td v-if="!isAdmin">{{ formatFcfa(bid.amount) }}</td>
                        <td>{{ formatDate(bid.at) }}</td>
                        <td>{{ bid.status }}</td>
                        <td class="py-2">
                            <button
                                v-if="isWinning(bid)"
                                type="button"
                                class="rounded-lg bg-violet-700 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                                :disabled="sendingId === bid.id"
                                @click="sendMessage(item, bid)"
                            >
                                {{ sendingId === bid.id ? 'Envoi…' : 'Message' }}
                            </button>
                            <p v-if="notices[bid.id]" class="mt-1 max-w-xs text-xs" :class="noticeClass(bid.id)">{{ notices[bid.id] }}</p>
                        </td>
                    </tr>
                    <tr v-if="!visibleBids(item).length">
                        <td :colspan="isAdmin ? 4 : 5" class="py-3 text-slate-500">Aucune offre pour le moment.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { formatFcfa } from '../../config/encheres-mock-data';
import { canAccessVenteEncheresAdmin, canAccessVenteEncheresComite } from '../../config/vente-encheres-access';
import { fetchCommitteeAuctions, sendWinnerMessage, verifyCommitteeCode } from '../../services/encheresApi';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);
const loadError = ref('');
const items = ref([]);
const codes = reactive({});
const errors = reactive({});
const offers = reactive({});
const pendingKey = ref('');
const sendingId = ref(null);
const notices = reactive({});
const noticeOk = reactive({});
const isAdmin = computed(() => canAccessVenteEncheresAdmin(auth.baseUser ?? auth.user));

function visibleBids(item) {
    if (item?.requires_code === false) {
        return item.bids || [];
    }

    return offers[item.auction_key] || null;
}

function isWinning(bid) {
    return String(bid?.status || '').toLowerCase().includes('gagn');
}

function noticeClass(bidId) {
    return noticeOk[bidId] ? 'text-emerald-700' : 'text-red-700';
}

async function sendMessage(item, bid) {
    sendingId.value = bid.id;
    notices[bid.id] = '';
    noticeOk[bid.id] = false;
    try {
        const result = await sendWinnerMessage(item.auction_key, bid.id);
        notices[bid.id] = result.message || 'Message envoyé.';
        noticeOk[bid.id] = true;
    } catch (err) {
        notices[bid.id] = err.response?.data?.message || 'Envoi impossible.';
        noticeOk[bid.id] = false;
    } finally {
        sendingId.value = null;
    }
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        items.value = await fetchCommitteeAuctions();
    } catch (err) {
        loadError.value = err.response?.data?.message || 'Impossible de charger les biens du comité.';
    } finally {
        loading.value = false;
    }
}

async function unlock(item) {
    const code = String(codes[item.auction_key] || '').trim();
    errors[item.auction_key] = '';
    if (!/^\d{3}$/.test(code)) {
        errors[item.auction_key] = 'Saisissez les 3 chiffres reçus par e-mail.';
        return;
    }

    pendingKey.value = item.auction_key;
    try {
        const result = await verifyCommitteeCode(item.auction_key, code);
        offers[item.auction_key] = result.bids || [];
        codes[item.auction_key] = '';
    } catch (err) {
        errors[item.auction_key] = err.response?.data?.message || 'Code refusé.';
    } finally {
        pendingKey.value = '';
    }
}

onMounted(() => {
    if (!canAccessVenteEncheresComite(auth.baseUser ?? auth.user)) {
        router.replace({ name: 'vente-encheres.home' });
        return;
    }
    load();
});
</script>
