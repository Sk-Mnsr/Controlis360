<template>
    <div class="min-h-full w-full space-y-6">
        <section class="min-h-[calc(100vh-4rem)] w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gouvernance IT · Retroplanning</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ pageTitle }}</h2>
                </div>
                <RouterLink
                    :to="{ name: 'gouvernance-it.govstrat-itr' }"
                    class="text-sm font-medium text-slate-500 hover:text-slate-800"
                >
                    ← Retour à GovStrat IT-R
                </RouterLink>
            </div>

            <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
            <p v-if="loading" class="mt-6 text-sm text-slate-500">Chargement…</p>

            <div
                v-else-if="!ensembles.length"
                class="mt-6 overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between gap-3 bg-[#a3181f] px-3 py-2.5 text-white">
                    <h3 class="flex-1 text-center text-base font-bold tracking-wide">{{ pageTitle }}</h3>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-500 text-xl font-light leading-none text-white hover:bg-sky-400 disabled:opacity-50"
                        title="Ajouter un ensemble"
                        :disabled="addingEnsemble"
                        @click="addEnsemble"
                    >
                        {{ addingEnsemble ? '…' : '+' }}
                    </button>
                </div>
                <p class="px-4 py-8 text-center text-sm text-slate-500">
                    Aucun ensemble. Cliquez sur le <strong>+</strong> pour en créer un.
                </p>
            </div>

            <div v-else class="mt-6 space-y-8">
                <div
                    v-for="(ensemble, ensembleIndex) in ensembles"
                    :key="ensemble.id"
                    class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-800">{{ ensemble.label }}</h3>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                            title="Supprimer cet ensemble"
                            :disabled="ensemble.deleting"
                            @click="deleteEnsemble(ensemble)"
                        >
                            {{ ensemble.deleting ? 'Suppression…' : 'Supprimer l’ensemble ×' }}
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white shadow-sm">
                        <table class="min-w-[1100px] w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-[#a3181f] text-white">
                                    <th
                                        colspan="7"
                                        class="border border-[#7f1218] px-3 py-2.5 text-center text-base font-bold tracking-wide"
                                    >
                                        {{ pageTitle }}
                                    </th>
                                    <th
                                        v-if="canEdit"
                                        class="border border-[#7f1218] px-2 py-2.5 w-28 text-center"
                                    >
                                        <button
                                            v-if="ensembleIndex === 0"
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-500 text-xl font-light leading-none text-white hover:bg-sky-400 disabled:opacity-50"
                                            title="Ajouter un ensemble"
                                            :disabled="addingEnsemble"
                                            @click="addEnsemble"
                                        >
                                            {{ addingEnsemble ? '…' : '+' }}
                                        </button>
                                    </th>
                                </tr>
                                <tr class="bg-slate-700 text-left text-xs uppercase tracking-wide text-white">
                                    <th class="border border-slate-600 px-2 py-2 font-semibold w-28"></th>
                                    <th class="border border-slate-600 px-2 py-2 font-semibold">Activities</th>
                                    <th class="border border-slate-600 px-2 py-2 font-semibold">Due Date</th>
                                    <th class="border border-slate-600 px-2 py-2 font-semibold">Status</th>
                                    <th class="border border-slate-600 px-2 py-2 font-semibold">Owner</th>
                                    <th class="border border-slate-600 px-2 py-2 font-semibold">Comments1</th>
                                    <th class="border border-slate-600 px-2 py-2 font-semibold">Comments2</th>
                                    <th v-if="canEdit" class="border border-slate-600 px-2 py-2 font-semibold w-28">Actions</th>
                                </tr>
                            </thead>

                            <tbody v-for="section in categorySections(ensemble)" :key="`${ensemble.id}-${section.key}`">
                                <tr v-if="!section.rows.length">
                                    <td class="border border-slate-500 bg-slate-100 px-2 py-8 text-center align-middle">
                                        <span
                                            class="inline-block text-sm font-bold tracking-wide text-slate-800"
                                            style="writing-mode: vertical-rl; text-orientation: mixed; transform: rotate(180deg);"
                                        >
                                            {{ section.label }}
                                        </span>
                                    </td>
                                    <td colspan="6" class="border border-slate-400 px-4 py-6 text-center text-slate-500">
                                        Aucune ligne — cliquez sur <strong>+</strong> pour en ajouter.
                                    </td>
                                    <td v-if="canEdit" class="border border-slate-400 px-2 py-2 text-center align-top">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-500 text-xl font-light leading-none text-white hover:bg-sky-600 disabled:opacity-50"
                                            title="Ajouter une ligne"
                                            :disabled="isAdding(ensemble.id, section.key)"
                                            @click="addRow(ensemble, section.key)"
                                        >
                                            {{ isAdding(ensemble.id, section.key) ? '…' : '+' }}
                                        </button>
                                    </td>
                                </tr>

                                <tr
                                    v-for="(row, rowIndex) in section.rows"
                                    :key="row.localKey || row.id"
                                    class="align-middle"
                                    :class="rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50'"
                                >
                                    <td
                                        v-if="rowIndex === 0"
                                        class="border border-slate-500 bg-slate-100 px-2 py-2 text-center align-middle"
                                        :rowspan="section.rows.length"
                                    >
                                        <span
                                            class="inline-block py-3 text-sm font-bold tracking-wide text-slate-800"
                                            style="writing-mode: vertical-rl; text-orientation: mixed; transform: rotate(180deg);"
                                        >
                                            {{ section.label }}
                                        </span>
                                    </td>

                                    <td class="border border-slate-400 px-2 py-1.5">
                                        <input
                                            v-if="canEdit && row.editing"
                                            v-model="row.activity"
                                            type="text"
                                            class="w-full min-w-[14rem] rounded border border-slate-300 px-1.5 py-1 text-sm"
                                            :class="row.is_subheader ? 'italic text-[#a3181f]' : ''"
                                            placeholder="Activité"
                                        />
                                        <span
                                            v-else
                                            :class="row.is_subheader ? 'italic font-medium text-[#a3181f]' : 'text-slate-800'"
                                        >
                                            {{ row.activity || '—' }}
                                        </span>
                                        <label
                                            v-if="canEdit && row.editing"
                                            class="mt-1 flex items-center gap-1 text-[10px] text-slate-500"
                                        >
                                            <input v-model="row.is_subheader" type="checkbox" class="rounded border-slate-300" />
                                            Sous-titre
                                        </label>
                                    </td>

                                    <td class="border border-slate-400 px-2 py-1.5">
                                        <input
                                            v-if="canEdit && row.editing"
                                            v-model="row.due_date"
                                            type="date"
                                            class="w-full min-w-[8rem] rounded border border-slate-300 bg-white px-1.5 py-1 text-sm outline-none focus:border-sky-400"
                                        />
                                        <span v-else class="whitespace-nowrap">{{ formatDate(row.due_date) }}</span>
                                    </td>

                                    <td
                                        class="border border-slate-400 px-2 py-1.5 text-center font-semibold"
                                        :class="statusClass(row.status)"
                                    >
                                        <select
                                            v-if="canEdit && row.editing"
                                            v-model="row.status"
                                            class="w-full rounded border border-slate-300 bg-white/90 px-1.5 py-1 text-sm"
                                        >
                                            <option v-for="(label, key) in statuses" :key="key" :value="key">
                                                {{ label }}
                                            </option>
                                        </select>
                                        <span v-else>{{ statusLabel(row.status) }}</span>
                                    </td>

                                    <td
                                        class="border border-slate-400 px-2 py-1.5"
                                        :class="isOwnerOfRow(row) ? 'bg-[#d4a017] font-semibold text-slate-900' : ''"
                                    >
                                        <select
                                            v-if="canEdit && row.editing"
                                            v-model="row.owner"
                                            class="w-full min-w-[110px] rounded border border-slate-300 px-1.5 py-1 text-sm outline-none focus:border-sky-400"
                                            :class="isOwnerOfRow(row) ? 'bg-[#d4a017]' : 'bg-white'"
                                        >
                                            <option value="">—</option>
                                            <option
                                                v-if="row.owner && !owners.includes(row.owner)"
                                                :value="row.owner"
                                            >
                                                {{ row.owner }}
                                            </option>
                                            <option v-for="owner in owners" :key="owner" :value="owner">
                                                {{ owner }}
                                            </option>
                                        </select>
                                        <span v-else>{{ row.owner || '—' }}</span>
                                    </td>

                                    <td class="border border-slate-400 px-2 py-1.5 min-w-[140px] max-w-[200px]">
                                        <div class="flex items-start gap-1.5">
                                            <button
                                                type="button"
                                                class="mt-0.5 shrink-0 text-sky-600 hover:text-sky-800"
                                                title="Comments1"
                                                @click="openCommentModal(row, 'comments1')"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M18 10c0 3.866-3.582 7-8 7a8.84 8.84 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                            <div class="min-w-0 flex-1">
                                                <p class="line-clamp-2 text-slate-700">{{ row.comments1 || '—' }}</p>
                                                <button
                                                    v-if="row.comments1"
                                                    type="button"
                                                    class="mt-0.5 text-[10px] italic text-sky-600 hover:underline"
                                                    @click="openCommentModal(row, 'comments1')"
                                                >
                                                    Voir plus
                                                </button>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="border border-slate-400 px-2 py-1.5 min-w-[140px] max-w-[200px]">
                                        <div class="flex items-start gap-1.5">
                                            <button
                                                type="button"
                                                class="mt-0.5 shrink-0 text-sky-600 hover:text-sky-800"
                                                title="Comments2"
                                                @click="openCommentModal(row, 'comments2')"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M18 10c0 3.866-3.582 7-8 7a8.84 8.84 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                            <div class="min-w-0 flex-1">
                                                <p class="line-clamp-2 text-slate-700">{{ row.comments2 || '—' }}</p>
                                                <button
                                                    v-if="row.comments2"
                                                    type="button"
                                                    class="mt-0.5 text-[10px] italic text-sky-600 hover:underline"
                                                    @click="openCommentModal(row, 'comments2')"
                                                >
                                                    Voir plus
                                                </button>
                                            </div>
                                        </div>
                                    </td>

                                    <td v-if="canEdit" class="border border-slate-400 px-2 py-1.5">
                                        <div class="flex flex-wrap items-center justify-end gap-1">
                                            <button
                                                v-if="rowIndex === 0"
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-500 text-lg font-light leading-none text-white hover:bg-sky-600 disabled:opacity-50"
                                                title="Ajouter une ligne dans cette partie"
                                                :disabled="isAdding(ensemble.id, section.key)"
                                                @click="addRow(ensemble, section.key)"
                                            >
                                                {{ isAdding(ensemble.id, section.key) ? '…' : '+' }}
                                            </button>
                                            <button
                                                v-if="!row.editing"
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-sky-300 text-sky-600 hover:bg-sky-50"
                                                title="Modifier"
                                                @click="row.editing = true"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button
                                                v-else
                                                type="button"
                                                class="inline-flex h-7 items-center justify-center rounded border border-emerald-400 bg-emerald-50 px-2 text-[10px] font-semibold text-emerald-800 hover:bg-emerald-100 disabled:opacity-50"
                                                title="Enregistrer"
                                                :disabled="row.saving"
                                                @click="saveRow(row)"
                                            >
                                                {{ row.saving ? '…' : 'Save' }}
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-red-300 text-red-600 hover:bg-red-50 disabled:opacity-50"
                                                title="Supprimer"
                                                :disabled="row.saving || row.deleting"
                                                @click="deleteRow(ensemble, row)"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="commentModal.open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeCommentModal"
        >
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">{{ commentModal.title }}</h3>
                    <button
                        type="button"
                        class="rounded px-2 text-lg leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        @click="closeCommentModal"
                    >
                        ×
                    </button>
                </div>
                <div class="p-4">
                    <textarea
                        v-model="commentModal.draft"
                        rows="6"
                        class="w-full resize-y rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-400"
                        :readonly="!commentModal.canEdit"
                        placeholder="Saisir un commentaire…"
                    />
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-4 py-3">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        @click="closeCommentModal"
                    >
                        {{ commentModal.canEdit ? 'Annuler' : 'Fermer' }}
                    </button>
                    <button
                        v-if="commentModal.canEdit"
                        type="button"
                        class="rounded-lg bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800 disabled:opacity-50"
                        :disabled="commentModal.saving"
                        @click="saveCommentModal"
                    >
                        {{ commentModal.saving ? '…' : 'Enregistrer' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const DEFAULT_CATEGORIES = ['Legal', 'Technique', 'Contrôle'];

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const addingEnsemble = ref(false);
const addingKey = ref(null);
const error = ref('');
const canEdit = ref(false);
const categories = ref([...DEFAULT_CATEGORIES]);
const owners = ref([]);
const statuses = ref({
    completed: 'Completed',
    in_progress: 'In progress',
    en_attente: 'En attente',
    not_started: 'Not Started',
});
const ensembles = ref([]);

const commentModal = reactive({
    open: false,
    row: null,
    field: 'comments1',
    title: 'Comments1',
    draft: '',
    canEdit: false,
    saving: false,
});

const activityId = computed(() => Number(route.params.activityId));

const pageTitle = computed(() => 'Retroplanning');

function normalizeCategory(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .toLowerCase();
}

function categorySections(ensemble) {
    return categories.value.map((label) => ({
        key: label,
        label,
        rows: (ensemble.items || []).filter(
            (row) => normalizeCategory(row.category) === normalizeCategory(label),
        ),
    }));
}

function statusLabel(status) {
    return statuses.value[status] || status || '—';
}

function formatDate(value) {
    if (!value) return '—';
    const raw = String(value).slice(0, 10);
    const parts = raw.split('-');
    if (parts.length !== 3 || parts[0].length !== 4) return '—';
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function normalizeDueDate(value) {
    if (!value) return '';
    const raw = String(value).slice(0, 10);
    return /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : '';
}

function isOwnerOfRow(row) {
    const name = auth.user?.name?.trim();
    if (!name || !row?.owner) {
        return false;
    }

    return name.toLowerCase() === String(row.owner).trim().toLowerCase();
}

function statusClass(status) {
    switch (status) {
        case 'completed':
            return 'bg-lime-400 text-slate-900';
        case 'in_progress':
            return 'bg-amber-100 text-slate-900';
        case 'en_attente':
            return 'bg-slate-200 text-slate-800';
        case 'not_started':
            return 'bg-red-500 text-white';
        default:
            return 'bg-white text-slate-800';
    }
}

function mapItem(item, extras = {}) {
    return {
        localKey: `rp-${item.id || Math.random().toString(36).slice(2)}`,
        id: item.id ?? null,
        ensemble_id: item.ensemble_id ?? null,
        category: item.category ?? '',
        activity: item.activity ?? '',
        is_subheader: Boolean(item.is_subheader),
        due_date: normalizeDueDate(item.due_date),
        status: item.status || 'not_started',
        owner: item.owner ?? '',
        comments1: item.comments1 ?? '',
        comments2: item.comments2 ?? '',
        editing: false,
        saving: false,
        deleting: false,
        ...extras,
    };
}

function mapEnsemble(ensemble) {
    return {
        id: ensemble.id,
        label: ensemble.label || 'Ensemble',
        deleting: false,
        items: (ensemble.items || []).map((item) => mapItem(item)),
    };
}

function isAdding(ensembleId, category) {
    return addingKey.value === `${ensembleId}:${category}`;
}

async function load() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/gouvernance-it/activities/${activityId.value}/retroplanning`);
        const payload = data.data ?? data;
        statuses.value = payload.statuses || statuses.value;
        categories.value = payload.categories?.length ? payload.categories : [...DEFAULT_CATEGORIES];
        owners.value = payload.owners ?? [];
        canEdit.value = Boolean(payload.can_edit);
        ensembles.value = (payload.ensembles || []).map((ensemble) => mapEnsemble(ensemble));
    } catch (err) {
        error.value = err.response?.data?.message
            || err.response?.data?.errors?.auth?.[0]
            || 'Impossible de charger le rétroplanning.';
        if (err.response?.status === 403 || err.response?.status === 404) {
            setTimeout(() => router.replace({ name: 'gouvernance-it.govstrat-itr' }), 1200);
        }
    } finally {
        loading.value = false;
    }
}

async function addEnsemble() {
    if (!canEdit.value || addingEnsemble.value) return;
    addingEnsemble.value = true;
    error.value = '';

    try {
        const { data } = await api.post(`/gouvernance-it/activities/${activityId.value}/retroplanning/ensembles`);
        ensembles.value.push(mapEnsemble(data.data ?? data));
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible d’ajouter un ensemble.';
    } finally {
        addingEnsemble.value = false;
    }
}

async function deleteEnsemble(ensemble) {
    if (!confirm(`Supprimer « ${ensemble.label} » et toutes ses lignes ?`)) return;
    ensemble.deleting = true;
    error.value = '';

    try {
        await api.delete(`/gouvernance-it/activities/${activityId.value}/retroplanning/ensembles/${ensemble.id}`);
        ensembles.value = ensembles.value.filter((item) => item.id !== ensemble.id);
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible de supprimer l’ensemble.';
        ensemble.deleting = false;
    }
}

async function addRow(ensemble, category) {
    if (!canEdit.value) return;
    const key = `${ensemble.id}:${category}`;
    if (addingKey.value) return;
    addingKey.value = key;
    error.value = '';

    try {
        const { data } = await api.post(`/gouvernance-it/activities/${activityId.value}/retroplanning`, {
            ensemble_id: ensemble.id,
            category,
            activity: '',
            due_date: null,
            status: 'not_started',
            owner: '',
            comments1: '',
            comments2: '',
            is_subheader: false,
        });
        ensemble.items.push(mapItem(data.data ?? data, { editing: true }));
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible d’ajouter une ligne.';
    } finally {
        addingKey.value = null;
    }
}

async function saveRow(row, options = {}) {
    if (!row.id || row.saving) return;
    row.saving = true;
    error.value = '';

    try {
        const { data } = await api.put(
            `/gouvernance-it/activities/${activityId.value}/retroplanning/${row.id}`,
            {
                category: row.category,
                activity: row.activity,
                is_subheader: row.is_subheader,
                due_date: row.due_date || null,
                status: row.status || null,
                owner: row.owner || null,
                comments1: row.comments1 || null,
                comments2: row.comments2 || null,
            },
        );
        const payload = data.data ?? data;
        row.category = payload.category ?? row.category;
        row.activity = payload.activity ?? '';
        row.is_subheader = Boolean(payload.is_subheader);
        row.due_date = normalizeDueDate(payload.due_date);
        row.status = payload.status || 'not_started';
        row.owner = payload.owner ?? '';
        row.comments1 = payload.comments1 ?? '';
        row.comments2 = payload.comments2 ?? '';
        if (!options.keepEditing) {
            row.editing = false;
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible d’enregistrer la ligne.';
        throw err;
    } finally {
        row.saving = false;
    }
}

function openCommentModal(row, field) {
    if (!row?.id) return;
    commentModal.open = true;
    commentModal.row = row;
    commentModal.field = field;
    commentModal.title = field === 'comments2' ? 'Comments2' : 'Comments1';
    commentModal.draft = row[field] ?? '';
    commentModal.canEdit = canEdit.value;
    commentModal.saving = false;
}

function closeCommentModal() {
    commentModal.open = false;
    commentModal.row = null;
    commentModal.field = 'comments1';
    commentModal.title = 'Comments1';
    commentModal.draft = '';
    commentModal.canEdit = false;
    commentModal.saving = false;
}

async function saveCommentModal() {
    const row = commentModal.row;
    if (!row || !commentModal.canEdit) return;

    commentModal.saving = true;
    row[commentModal.field] = commentModal.draft;
    try {
        await saveRow(row, { keepEditing: row.editing });
        closeCommentModal();
    } catch {
        // error already set in saveRow
    } finally {
        commentModal.saving = false;
    }
}

async function deleteRow(ensemble, row) {
    if (!row.id) {
        ensemble.items = ensemble.items.filter((item) => item.localKey !== row.localKey);
        return;
    }

    if (!confirm('Supprimer cette ligne ?')) return;
    row.deleting = true;
    error.value = '';

    try {
        await api.delete(`/gouvernance-it/activities/${activityId.value}/retroplanning/${row.id}`);
        ensemble.items = ensemble.items.filter((item) => item.id !== row.id);
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible de supprimer la ligne.';
        row.deleting = false;
    }
}

onMounted(load);
</script>
