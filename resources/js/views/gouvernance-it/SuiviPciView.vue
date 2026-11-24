<template>
    <div class="space-y-5">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="h-1.5 bg-[#1d4ed8]" />
            <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <RouterLink :to="{ name: 'gouvernance-it.home' }" class="text-sm font-medium text-blue-700 hover:text-blue-800">
                        ← Gouvernance IT
                    </RouterLink>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Tableau de bord — Doc Gouvernance</h2>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        La prochaine revue, les jours restants et le statut sont calculés à partir de la dernière revue et de la périodicité.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label v-if="canSelectFiliale && filiales.length" class="text-sm text-slate-600">
                        Filiale
                        <select
                            v-model="selectedEnvironmentId"
                            class="ml-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            @change="loadRows"
                        >
                            <option v-for="filiale in filiales" :key="filiale.id" :value="filiale.id">
                                {{ filiale.name || filiale.code }}
                            </option>
                        </select>
                    </label>
                    <button
                        v-if="permissions.can_create"
                        type="button"
                        class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
                        @click="openCreate"
                    >
                        Ajouter un document
                    </button>
                </div>
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <button
                v-for="card in statCards"
                :key="card.key"
                type="button"
                class="rounded-2xl border bg-white p-4 text-left shadow-sm"
                :class="statusFilter === card.filter ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'"
                @click="statusFilter = statusFilter === card.filter ? '' : card.filter"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                <p class="mt-2 text-2xl font-semibold" :class="card.valueClass">{{ stats[card.key] ?? 0 }}</p>
            </button>
        </div>

        <p v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p>
        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement…
        </div>

        <div v-else-if="!filteredRows.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            {{ rows.length ? 'Aucun document pour ce filtre.' : 'Aucun document enregistré.' }}
        </div>

        <div v-else class="space-y-6">
            <section v-for="group in folderGroups" :key="group.status">
                <h3 class="mb-3 text-sm font-semibold" :class="group.titleClass">{{ group.label }}</h3>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <button
                        v-for="row in group.rows"
                        :key="row.id"
                        type="button"
                        class="text-left"
                        @click="openFiles(row)"
                    >
                        <span class="block h-3 w-20 rounded-t-lg" :class="group.tabClass" />
                        <span
                            class="block rounded-xl rounded-tl-none border px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="group.bodyClass"
                        >
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide" :class="group.titleClass">{{ row.reference }}</span>
                                <span
                                    v-if="row.attachments?.length"
                                    class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200"
                                >
                                    {{ row.attachments.length }}
                                </span>
                            </span>
                            <span class="mt-2 block text-sm font-semibold text-slate-900">{{ row.document_name }}</span>
                            <span
                                v-if="row.workflow_status === 'pending_validation'"
                                class="mt-2 inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-semibold text-orange-800"
                            >
                                À valider
                            </span>
                            <span class="mt-2 block text-xs" :class="group.titleClass">
                                {{ formatDays(row.days_remaining) }}
                                <template v-if="row.reminder_label"> · {{ row.reminder_label }}</template>
                            </span>
                        </span>
                    </button>
                </div>
            </section>
        </div>

        <div v-if="formOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <form class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @submit.prevent="save">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">{{ editingId ? 'Modifier le document' : 'Ajouter un document' }}</h3>
                    <button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-slate-500" @click="formOpen = false">×</button>
                </div>
                <p v-if="formError" class="mt-3 text-sm text-red-600">{{ formError }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="text-sm text-slate-700">
                        Référence
                        <input v-model="form.reference" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="PCI-001, générée si vide">
                    </label>
                    <label class="text-sm text-slate-700">
                        Document / Procédure
                        <input v-model="form.document_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700">
                        Catégorie
                        <input v-model="form.category" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Gouvernance, Sécurité, Continuité…">
                    </label>
                    <label class="text-sm text-slate-700">
                        Direction / Service
                        <input v-model="form.direction" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700">
                        Responsable
                        <input v-model="form.responsible" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700">
                        Version
                        <input v-model="form.version" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="V1">
                    </label>
                    <label class="text-sm text-slate-700">
                        Date de création
                        <input v-model="form.created_on" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700">
                        Date dernière revue
                        <input v-model="form.last_review_date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700">
                        Périodicité (mois)
                        <input v-model.number="form.periodicity_months" type="number" min="1" max="120" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700">
                        Date de validation
                        <input v-model="form.validated_on" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700 sm:col-span-2">
                        Validé par
                        <input v-model="form.validated_by" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm text-slate-700 sm:col-span-2">
                        Observations / Actions
                        <textarea v-model="form.observations" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="formOpen = false">Annuler</button>
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" :disabled="saving">
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>

        <div v-if="filesTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-blue-800">{{ filesTarget.reference }}</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ filesTarget.document_name }}</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusClass(filesTarget.status)">
                                {{ filesTarget.status_label }}
                            </span>
                            <span v-if="filesTarget.reminder_label" class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                {{ filesTarget.reminder_label }}
                            </span>
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold" :class="daysClass(filesTarget)">
                                {{ formatDays(filesTarget.days_remaining) }}
                            </span>
                            <span v-if="filesTarget.version" class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                {{ filesTarget.version }}
                            </span>
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="filesTarget.workflow_status === 'pending_validation' ? 'bg-orange-100 text-orange-800' : 'bg-emerald-100 text-emerald-800'"
                            >
                                {{ filesTarget.workflow_status_fr || 'Validé' }}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg border border-slate-200 px-2.5 py-1 text-slate-500" @click="filesTarget = null">×</button>
                </div>

                <div class="space-y-6 px-6 py-5">
                    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="fact in documentFacts" :key="fact.label" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ fact.label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ fact.value }}</dd>
                        </div>
                    </dl>

                    <section v-if="filesTarget.observations" class="rounded-xl border border-slate-200 px-4 py-3">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observations / Actions</h4>
                        <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ filesTarget.observations }}</p>
                    </section>

                    <section class="rounded-xl border border-slate-200 px-4 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-slate-900">Fichiers</h4>
                            <span class="text-xs text-slate-500">{{ filesTarget.attachments?.length || 0 }}</span>
                        </div>
                        <p v-if="filesError" class="mt-3 text-sm text-red-600">{{ filesError }}</p>
                        <ul v-if="filesTarget.attachments?.length" class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200">
                            <li v-for="file in filesTarget.attachments" :key="file.path" class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-slate-900">{{ file.name }}</span>
                                    <span class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">{{ file.right || 'Lecture' }}</span>
                                        <span class="truncate">{{ file.original_name || file.name }}</span>
                                    </span>
                                </span>
                                <span class="flex shrink-0 gap-2">
                                    <button v-if="canPreview(file)" type="button" class="text-sm font-medium text-blue-700 hover:underline" @click="previewAttachment(file)">Voir</button>
                                    <button type="button" class="text-sm font-medium text-blue-700 hover:underline" @click="downloadAttachment(file)">Télécharger</button>
                                    <button v-if="permissions.can_edit" type="button" class="text-sm font-medium text-red-700 hover:underline" @click="removeFile(file)">Retirer</button>
                                </span>
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-sm text-slate-500">Aucun fichier sur ce document.</p>
                        <form v-if="permissions.can_edit" class="mt-4 space-y-3" @submit.prevent="uploadFiles">
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-600">
                                            <th class="px-3 py-2 font-semibold">Nom</th>
                                            <th class="px-3 py-2 font-semibold">Fichier</th>
                                            <th class="px-3 py-2 font-semibold">Droit</th>
                                            <th class="w-16 px-2 py-2" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in pendingFiles" :key="item.key" class="border-b border-slate-100 last:border-0">
                                            <td class="px-3 py-2">
                                                <input v-model="item.name" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Saisir le nom">
                                            </td>
                                            <td class="px-3 py-2">
                                                <label class="inline-flex max-w-full cursor-pointer items-center rounded-lg border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50">
                                                    <span class="truncate">{{ item.file ? item.file.name : 'Choisir un fichier' }}</span>
                                                    <input class="sr-only" type="file" @change="onFilePicked(item, $event)">
                                                </label>
                                            </td>
                                            <td class="px-3 py-2">
                                                <select v-model="item.right" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                                    <option value="Lecture">Lecture</option>
                                                    <option value="Modification">Modification</option>
                                                    <option value="Confidentiel">Confidentiel</option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <button type="button" class="text-sm font-medium text-red-700 hover:underline" @click="removePending(item.key)">Retirer</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <button type="button" class="text-sm font-medium text-blue-700 hover:underline" @click="addPendingRow">
                                    Ajouter une ligne
                                </button>
                                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" :disabled="uploading">
                                    {{ uploading ? 'Envoi…' : 'Ajouter' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
                    <button
                        v-if="permissions.can_validate && filesTarget.workflow_status === 'pending_validation'"
                        type="button"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="validating"
                        @click="validateDocument"
                    >
                        {{ validating ? 'Validation…' : 'Valider' }}
                    </button>
                    <button
                        v-if="permissions.can_edit"
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                        @click="editFromDetail"
                    >
                        Modifier
                    </button>
                    <button
                        v-if="permissions.can_delete"
                        type="button"
                        class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700"
                        @click="askDelete(filesTarget)"
                    >
                        Retirer
                    </button>
                    <button type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="filesTarget = null">
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <div v-if="deleteTarget" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/40 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900">Retirer ce document ?</h3>
                <p class="mt-2 text-sm text-slate-600">{{ deleteTarget.reference }} — {{ deleteTarget.document_name }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="deleteTarget = null">Annuler</button>
                    <button type="button" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white" @click="confirmDelete">Retirer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../api/client';
import { downloadAttachment, isPreviewableAttachment, previewAttachment } from '../../utils/attachments';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const rows = ref([]);
const stats = ref({});
const filiales = ref([]);
const canSelectFiliale = ref(false);
const selectedEnvironmentId = ref(null);
const permissions = ref({ can_create: false, can_edit: false, can_delete: false, can_validate: false });
const validating = ref(false);
const statusFilter = ref('');
const formOpen = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const filesTarget = ref(null);
const filesError = ref('');
const pendingFiles = ref([]);
const uploading = ref(false);

const emptyForm = () => ({
    reference: '',
    document_name: '',
    category: '',
    direction: '',
    responsible: '',
    created_on: '',
    last_review_date: '',
    periodicity_months: 12,
    validated_on: '',
    validated_by: '',
    version: '',
    observations: '',
});

const form = reactive(emptyForm());

const statCards = [
    { key: 'enregistres', filter: '', label: 'Documents enregistrés', valueClass: 'text-slate-900' },
    { key: 'a_jour', filter: 'a_jour', label: 'Documents à jour', valueClass: 'text-emerald-700' },
    { key: 'echeance_90', filter: 'echeance_90', label: 'Échéances dans 90 jours', valueClass: 'text-amber-700' },
    { key: 'en_retard', filter: 'en_retard', label: 'Documents en retard', valueClass: 'text-red-700' },
    { key: 'a_renseigner', filter: 'a_renseigner', label: 'À renseigner', valueClass: 'text-slate-600' },
];

const folderPalette = {
    en_retard: {
        label: 'En retard',
        titleClass: 'text-red-700',
        tabClass: 'bg-red-600',
        bodyClass: 'border-red-200 bg-red-50',
    },
    echeance_7: {
        label: 'Échéance ≤ 7 jours',
        titleClass: 'text-orange-700',
        tabClass: 'bg-orange-500',
        bodyClass: 'border-orange-200 bg-orange-50',
    },
    echeance_90: {
        label: 'Échéance ≤ 90 jours',
        titleClass: 'text-amber-700',
        tabClass: 'bg-amber-400',
        bodyClass: 'border-amber-200 bg-amber-50',
    },
    a_jour: {
        label: 'À jour',
        titleClass: 'text-emerald-700',
        tabClass: 'bg-emerald-600',
        bodyClass: 'border-emerald-200 bg-emerald-50',
    },
    a_renseigner: {
        label: 'À renseigner',
        titleClass: 'text-slate-600',
        tabClass: 'bg-slate-400',
        bodyClass: 'border-slate-200 bg-slate-50',
    },
};

const filteredRows = computed(() => {
    if (!statusFilter.value) return rows.value;
    if (statusFilter.value === 'echeance_90') {
        return rows.value.filter((row) => row.status === 'echeance_90' || row.status === 'echeance_7');
    }
    return rows.value.filter((row) => row.status === statusFilter.value);
});

const folderGroups = computed(() => Object.entries(folderPalette)
    .map(([status, palette]) => ({
        status,
        ...palette,
        rows: filteredRows.value.filter((row) => row.status === status),
    }))
    .filter((group) => group.rows.length));

const documentFacts = computed(() => {
    const row = filesTarget.value;
    if (!row) return [];

    return [
        { label: 'Catégorie', value: row.category || '—' },
        { label: 'Direction / Service', value: row.direction || '—' },
        { label: 'Responsable', value: row.responsible || '—' },
        { label: 'Filiale', value: row.environment?.name || '—' },
        { label: 'Date de création', value: formatDate(row.created_on) },
        { label: 'Dernière revue', value: formatDate(row.last_review_date) },
        { label: 'Périodicité', value: row.periodicity_months ? `${row.periodicity_months} mois` : '—' },
        { label: 'Prochaine revue', value: formatDate(row.next_review_date) },
        { label: 'Jours restants', value: formatDays(row.days_remaining) },
        { label: 'Date de validation', value: formatDate(row.validated_on) },
        { label: 'Validé par', value: row.validated_by || '—' },
        { label: 'Validation responsable', value: row.workflow_status_fr || 'Validé' },
        { label: 'Validé par le responsable', value: row.workflow_validator_name || '—' },
        { label: 'Version', value: row.version || '—' },
    ];
});

function formatDate(value) {
    if (!value) return '—';
    const [year, month, day] = String(value).slice(0, 10).split('-');
    if (!year || !month || !day) return value;
    return `${day}/${month}/${year}`;
}

function formatDays(value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${value} j`;
}

function daysClass(row) {
    if (row.days_remaining === null || row.days_remaining === undefined) return 'text-slate-400';
    if (row.days_remaining < 0) return 'text-red-700';
    if (row.days_remaining <= 7) return 'text-orange-700';
    if (row.days_remaining <= 90) return 'text-amber-700';
    return 'text-emerald-700';
}

function statusClass(status) {
    if (status === 'a_jour') return 'bg-emerald-100 text-emerald-800';
    if (status === 'echeance_7') return 'bg-orange-100 text-orange-800';
    if (status === 'echeance_90') return 'bg-amber-100 text-amber-800';
    if (status === 'en_retard') return 'bg-red-100 text-red-800';
    return 'bg-slate-100 text-slate-600';
}

function apiError(err, fallback) {
    const data = err.response?.data ?? {};
    const errors = data.errors;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat().find(Boolean);
        if (first) return String(first);
    }
    if (typeof data.message === 'string' && data.message.trim() && !data.message.includes('SQLSTATE')) {
        return data.message;
    }
    return fallback;
}

function applyPayload(payload) {
    rows.value = payload?.rows ?? [];
    stats.value = payload?.stats ?? {};
    filiales.value = payload?.filiales ?? [];
    canSelectFiliale.value = Boolean(payload?.can_select_filiale);
    permissions.value = payload?.permissions ?? permissions.value;
    if (payload?.environment_id) {
        selectedEnvironmentId.value = payload.environment_id;
    }
}

async function loadRows() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/pci-documents', {
            params: selectedEnvironmentId.value ? { environment_id: selectedEnvironmentId.value } : {},
        });
        applyPayload(data?.data ?? data);
    } catch (err) {
        error.value = apiError(err, 'Impossible de charger Doc Gouvernance.');
    } finally {
        loading.value = false;
    }
}

function fillForm(row) {
    Object.assign(form, emptyForm(), {
        reference: row?.reference ?? '',
        document_name: row?.document_name ?? '',
        category: row?.category ?? '',
        direction: row?.direction ?? '',
        responsible: row?.responsible ?? '',
        created_on: row?.created_on ?? '',
        last_review_date: row?.last_review_date ?? '',
        periodicity_months: row?.periodicity_months ?? 12,
        validated_on: row?.validated_on ?? '',
        validated_by: row?.validated_by ?? '',
        version: row?.version ?? '',
        observations: row?.observations ?? '',
    });
}

function openCreate() {
    editingId.value = null;
    formError.value = '';
    fillForm(null);
    formOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    formError.value = '';
    fillForm(row);
    formOpen.value = true;
}

function askDelete(row) {
    deleteTarget.value = row;
}

function canPreview(file) {
    return isPreviewableAttachment(file);
}

function blankFileRow() {
    return {
        key: `row-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
        file: null,
        name: '',
        right: 'Lecture',
    };
}

function openFiles(row) {
    filesError.value = '';
    pendingFiles.value = [blankFileRow()];
    filesTarget.value = row;
}

function addPendingRow() {
    pendingFiles.value = [...pendingFiles.value, blankFileRow()];
}

function editFromDetail() {
    const row = filesTarget.value;
    if (!row) return;
    filesTarget.value = null;
    openEdit(row);
}

function onFilePicked(item, event) {
    const file = event.target.files?.[0] ?? null;
    if (file && file.size > 20 * 1024 * 1024) {
        filesError.value = 'Chaque fichier doit faire au plus 20 Mo.';
        event.target.value = '';
        return;
    }
    item.file = file;
    filesError.value = '';
    event.target.value = '';
}

function removePending(key) {
    pendingFiles.value = pendingFiles.value.filter((item) => item.key !== key);
    if (!pendingFiles.value.length) {
        pendingFiles.value = [blankFileRow()];
    }
}

function replaceRow(updated) {
    rows.value = rows.value.map((row) => (row.id === updated.id ? updated : row));
    if (filesTarget.value?.id === updated.id) {
        filesTarget.value = updated;
    }
}

async function uploadFiles() {
    const ready = pendingFiles.value.filter((item) => item.file);
    if (!filesTarget.value || !ready.length) {
        filesError.value = 'Choisissez au moins un fichier.';
        return;
    }
    if (ready.some((item) => !item.name.trim())) {
        filesError.value = 'Saisissez le nom de chaque fichier.';
        return;
    }
    uploading.value = true;
    filesError.value = '';
    const body = new FormData();
    ready.forEach((item) => {
        body.append('attachments[]', item.file);
        body.append('attachment_names[]', item.name.trim());
        body.append('attachment_rights[]', item.right);
    });
    try {
        const { data } = await api.post(`/pci-documents/${filesTarget.value.id}/attachments`, body);
        replaceRow(data?.data ?? data);
        pendingFiles.value = [blankFileRow()];
    } catch (err) {
        filesError.value = apiError(err, 'Ajout du fichier impossible.');
    } finally {
        uploading.value = false;
    }
}

async function validateDocument() {
    if (!filesTarget.value) return;
    validating.value = true;
    filesError.value = '';
    try {
        const { data } = await api.post(`/pci-documents/${filesTarget.value.id}/validate`);
        replaceRow(data?.data ?? data);
    } catch (err) {
        filesError.value = apiError(err, 'Validation impossible.');
    } finally {
        validating.value = false;
    }
}

async function removeFile(file) {
    if (!filesTarget.value) return;
    filesError.value = '';
    const body = new FormData();
    body.append('remove_paths[]', file.path);
    try {
        const { data } = await api.post(`/pci-documents/${filesTarget.value.id}/attachments`, body);
        replaceRow(data?.data ?? data);
    } catch (err) {
        filesError.value = apiError(err, 'Retrait du fichier impossible.');
    }
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = {
        ...form,
        reference: form.reference.trim() || null,
        periodicity_months: Number(form.periodicity_months) || 12,
        environment_id: selectedEnvironmentId.value,
    };
    try {
        if (editingId.value) {
            await api.put(`/pci-documents/${editingId.value}`, payload);
        } else {
            await api.post('/pci-documents', payload);
        }
        formOpen.value = false;
        await loadRows();
    } catch (err) {
        formError.value = apiError(err, 'Enregistrement impossible.');
    } finally {
        saving.value = false;
    }
}

async function confirmDelete() {
    const target = deleteTarget.value;
    if (!target) return;
    try {
        await api.delete(`/pci-documents/${target.id}`);
        deleteTarget.value = null;
        await loadRows();
    } catch (err) {
        error.value = apiError(err, 'Suppression impossible.');
        deleteTarget.value = null;
    }
}

onMounted(loadRows);
</script>
