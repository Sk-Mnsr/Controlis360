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
                    Les montants d’un bien restent masqués tant que chaque membre de son comité n’a pas confirmé son code personnel.
                </template>
            </p>
            <ol v-if="!isAdmin" class="mt-4 grid gap-2 text-sm text-slate-700 sm:grid-cols-3">
                <li class="rounded-xl bg-slate-50 px-3 py-2"><span class="font-semibold text-violet-800">1.</span> Saisissez votre code</li>
                <li class="rounded-xl bg-slate-50 px-3 py-2"><span class="font-semibold text-violet-800">2.</span> Les autres membres confirment</li>
                <li class="rounded-xl bg-slate-50 px-3 py-2"><span class="font-semibold text-violet-800">3.</span> Les offres s’affichent</li>
            </ol>
        </section>

        <p v-if="loading" class="text-sm text-slate-500">Chargement…</p>
        <p v-else-if="loadError" class="text-sm font-semibold text-red-700">{{ loadError }}</p>
        <p v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
            {{ isAdmin ? 'Aucun bien déposé pour le moment.' : 'Aucun bien ne vous a été assigné pour le moment.' }}
        </p>

        <section
            v-for="item in items"
            :key="item.auction_key"
            class="overflow-hidden rounded-2xl border bg-white shadow-sm"
            :class="item.requires_code !== false && item.ready ? 'border-emerald-200' : 'border-slate-200'"
        >
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ item.lot || item.auction_key }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ item.title }}</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="stateBadgeClass(item)">
                        {{ stateLabel(item) }}
                    </span>
                </div>

                <div v-if="item.requires_code !== false" class="mt-4">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="font-semibold text-slate-800">Comité de ce bien</span>
                        <span class="tabular-nums text-slate-600">{{ item.confirmed || 0 }}/{{ item.total || 0 }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="item.confirmed || 0" :aria-valuemin="0" :aria-valuemax="item.total || 0">
                        <div class="h-full rounded-full transition-all" :class="barClass(item)" :style="{ width: progressWidth(item) }"></div>
                    </div>
                    <ul v-if="item.members?.length" class="mt-3 flex flex-wrap gap-2">
                        <li
                            v-for="(member, index) in item.members"
                            :key="`${item.auction_key}-${index}`"
                            class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm"
                            :class="member.confirmed ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-amber-200 bg-amber-50 text-amber-950'"
                        >
                            <span
                                class="grid h-5 w-5 place-items-center rounded-full text-[11px] font-bold"
                                :class="member.confirmed ? 'bg-emerald-600 text-white' : 'bg-amber-200 text-amber-900'"
                                aria-hidden="true"
                            >{{ member.confirmed ? '✓' : '·' }}</span>
                            <span>{{ member.name }}</span>
                            <span v-if="member.self" class="text-[11px] font-semibold uppercase tracking-wide">vous</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-if="item.requires_code !== false && !item.ready" class="bg-slate-50 px-6 py-5">
                <p class="font-semibold text-slate-900">Offres masquées</p>
                <p class="mt-1 text-sm text-slate-600">
                    {{ offerLabel(item.bid_count) }}. Les montants et le bouton Message restent cachés jusqu’à la confirmation de chaque membre.
                </p>

                <form v-if="!item.self_confirmed" class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="unlock(item)">
                    <label class="text-sm font-semibold text-slate-800" :for="`code-${item.auction_key}`">
                        Votre code personnel
                        <input
                            :id="`code-${item.auction_key}`"
                            v-model="codes[item.auction_key]"
                            inputmode="numeric"
                            maxlength="3"
                            pattern="[0-9]{3}"
                            autocomplete="off"
                            class="mt-1 block w-28 rounded-lg border border-slate-300 bg-white px-3 py-2 text-center text-lg font-semibold tracking-[0.35em] text-slate-900 placeholder:tracking-normal placeholder:text-slate-300"
                            placeholder="•••"
                        />
                    </label>
                    <button
                        type="submit"
                        class="rounded-lg bg-violet-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="pendingKey === item.auction_key"
                    >
                        {{ pendingKey === item.auction_key ? 'Vérification…' : 'Confirmer mon code' }}
                    </button>
                </form>
                <p v-else class="mt-4 rounded-lg bg-white px-3 py-2 text-sm font-medium text-emerald-800">
                    Votre code est enregistré. Les offres s’afficheront dès que {{ waitingLabel(item) }}.
                </p>
                <p v-if="statuses[item.auction_key]" class="mt-3 text-sm font-semibold text-emerald-800">{{ statuses[item.auction_key] }}</p>
                <p v-if="errors[item.auction_key]" class="mt-2 text-sm font-semibold text-red-700">{{ errors[item.auction_key] }}</p>
            </div>

            <div v-else class="px-6 py-5">
                <p v-if="item.requires_code !== false" class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-900">
                    Tout le comité de ce bien a confirmé. Les offres sont visibles.
                </p>
                <p v-if="statuses[item.auction_key]" class="mb-3 text-sm font-semibold text-emerald-800">{{ statuses[item.auction_key] }}</p>
                <div class="overflow-x-auto">
                    <table v-if="visibleBids(item)" class="w-full min-w-[36rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-3 font-semibold">Client</th>
                                <th v-if="!isAdmin" class="py-2 pr-3 font-semibold">Montant</th>
                                <th class="py-2 pr-3 font-semibold">Date</th>
                                <th class="py-2 pr-3 font-semibold">Statut</th>
                                <th class="py-2 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="bid in visibleBids(item)"
                                :key="bid.id"
                                class="border-b border-slate-100"
                                :class="isWinning(bid) ? 'bg-emerald-50/70' : ''"
                            >
                                <td class="py-3 pr-3 font-medium text-slate-900">{{ bid.user_ref }}</td>
                                <td v-if="!isAdmin" class="py-3 pr-3 tabular-nums">{{ formatFcfa(bid.amount) }}</td>
                                <td class="py-3 pr-3 text-slate-600">{{ formatDate(bid.at) }}</td>
                                <td class="py-3 pr-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="isWinning(bid) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                                        {{ bid.status }}
                                    </span>
                                </td>
                                <td class="py-3">
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
                </div>
            </div>
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
const statuses = reactive({});
const offers = reactive({});
const pendingKey = ref('');
const sendingId = ref(null);
const notices = reactive({});
const noticeOk = reactive({});
const isAdmin = computed(() => canAccessVenteEncheresAdmin(auth.baseUser ?? auth.user));

function visibleBids(item) {
    if (item?.requires_code === false || item?.ready) {
        return offers[item.auction_key] || item.bids || [];
    }

    return null;
}

function offerLabel(count) {
    const total = Number(count) || 0;
    if (total === 0) return 'Aucune offre enregistrée';
    if (total === 1) return '1 offre enregistrée';
    return `${total} offres enregistrées`;
}

function progressWidth(item) {
    const total = Number(item?.total) || 0;
    if (total <= 0) return '0%';
    const ratio = Math.min(100, Math.round(((Number(item.confirmed) || 0) / total) * 100));
    return `${ratio}%`;
}

function barClass(item) {
    if (item?.ready) return 'bg-emerald-500';
    if ((item?.confirmed || 0) > 0) return 'bg-amber-400';
    return 'bg-slate-300';
}

function stateLabel(item) {
    if (item?.requires_code === false) return 'Visible';
    if (item?.ready) return 'Comité complet';
    if (item?.self_confirmed) return 'En attente des autres';
    return 'Votre code est requis';
}

function stateBadgeClass(item) {
    if (item?.requires_code === false || item?.ready) return 'bg-emerald-100 text-emerald-800';
    if (item?.self_confirmed) return 'bg-amber-100 text-amber-900';
    return 'bg-violet-100 text-violet-800';
}

function waitingLabel(item) {
    const names = item?.pending || [];
    if (!names.length) return 'les autres membres auront confirmé';
    if (names.length === 1) return `${names[0]} aura confirmé`;
    return `${names.join(', ')} auront confirmé`;
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
        item.self_confirmed = true;
        item.confirmed = result.confirmed;
        item.total = result.total;
        item.pending = result.pending || [];
        item.members = result.members || item.members;
        item.ready = Boolean(result.ready);
        codes[item.auction_key] = '';
        statuses[item.auction_key] = result.message || '';
        if (result.ready) {
            item.bids = result.bids || [];
            offers[item.auction_key] = item.bids;
        }
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
