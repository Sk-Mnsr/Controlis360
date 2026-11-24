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
                            class="ml-2 min-w-44 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                            @change="loadRows"
                        >
                            <option value="">Toutes les filiales</option>
                            <option v-for="filiale in filiales" :key="filiale.id" :value="String(filiale.id)">
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

        <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Documents</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    {{ filteredRows.length }} sur {{ rows.length }}
                    <template v-if="statusFilter"> · filtre actif</template>
                </p>
            </div>
            <ul class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <li v-for="(palette, status) in folderPalette" :key="status" class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="h-2.5 w-2.5 rounded-full" :class="palette.tabClass" />
                    {{ palette.label }}
                </li>
            </ul>
        </section>

        <p v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p>
        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement…
        </div>

        <div v-else-if="!filteredRows.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            {{ rows.length ? 'Aucun document pour ce filtre.' : 'Aucun document enregistré.' }}
        </div>

        <div v-else class="flex flex-wrap items-stretch gap-4">
                    <button
                        v-for="row in filteredRows"
                        :key="row.id"
                        type="button"
                        class="flex w-52 flex-col text-left"
                        @click="openFiles(row)"
                    >
                        <span class="block h-3 w-20 rounded-t-lg" :class="folderPalette[row.status].tabClass" />
                        <span
                            class="block flex-1 rounded-xl rounded-tl-none border px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="folderPalette[row.status].bodyClass"
                        >
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide" :class="folderPalette[row.status].titleClass">{{ row.reference }}</span>
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
                            <span
                                v-else-if="row.workflow_status === 'pending_deletion'"
                                class="mt-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-800"
                            >
                                Retrait à valider
                            </span>
                            <span
                                v-if="row.days_remaining !== null && row.days_remaining !== undefined"
                                class="mt-2 block text-xs"
                                :class="folderPalette[row.status].titleClass"
                            >
                                {{ formatDays(row.days_remaining) }}
                                <template v-if="row.reminder_label"> · {{ row.reminder_label }}</template>
                            </span>
                        </span>
                    </button>
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
                    <div class="sm:col-span-2">
                        <p class="text-sm font-medium text-slate-700">Fichiers</p>
                        <div v-if="existingFormFiles.length" class="mt-2 space-y-2">
                            <div v-for="item in existingFormFiles" :key="item.path" class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8.5rem_auto] sm:items-center">
                                <input v-model="item.name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Nom">
                                <p class="truncate rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">{{ item.original_name || item.name }}</p>
                                <select v-model="item.right" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                    <option value="Lecture">Lecture</option>
                                    <option value="Modification">Modification</option>
                                    <option value="Confidentiel">Confidentiel</option>
                                </select>
                                <button type="button" class="text-sm font-medium text-red-700 hover:underline" @click="removeExistingFile(item.path)">Retirer</button>
                            </div>
                        </div>
                        <div class="mt-2 space-y-2">
                            <div v-for="item in formFiles" :key="item.key" class="grid gap-2 rounded-xl border border-dashed border-slate-300 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8.5rem_auto] sm:items-center">
                                <input v-model="item.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nom">
                                <label class="flex cursor-pointer items-center rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span class="truncate">{{ item.file ? item.file.name : 'Choisir un fichier' }}</span>
                                    <input class="sr-only" type="file" @change="onFilePicked(item, $event)">
                                </label>
                                <select v-model="item.right" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                    <option value="Lecture">Lecture</option>
                                    <option value="Modification">Modification</option>
                                    <option value="Confidentiel">Confidentiel</option>
                                </select>
                                <button type="button" class="text-sm font-medium text-red-700 hover:underline" @click="removeFormFile(item.key)">Retirer</button>
                            </div>
                        </div>
                        <button type="button" class="mt-2 text-sm font-medium text-blue-700 hover:underline" @click="addFormFile">Ajouter une ligne</button>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="formOpen = false">Annuler</button>
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" :disabled="saving">
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>

        <div v-if="filesTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ filesTarget.reference }}</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ filesTarget.document_name }}</h3>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusClass(filesTarget.status)">
                                {{ filesTarget.status_label }}
                            </span>
                            <span v-if="filesTarget.reminder_label" class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                {{ filesTarget.reminder_label }}
                            </span>
                            <span
                                v-if="filesTarget.days_remaining !== null && filesTarget.days_remaining !== undefined"
                                class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold"
                                :class="daysClass(filesTarget)"
                            >
                                {{ formatDays(filesTarget.days_remaining) }}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg border border-slate-200 px-2.5 py-1 text-slate-500 hover:bg-slate-50" @click="filesTarget = null">×</button>
                </div>

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3" :class="validationTone.box">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide" :class="validationTone.title">Validation responsable</p>
                            <p class="mt-0.5 text-sm text-slate-800">{{ validationSentence }}</p>
                        </div>
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
                            v-if="permissions.can_validate && filesTarget.workflow_status === 'pending_deletion'"
                            type="button"
                            class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white"
                            @click="askConfirmDeletion"
                        >
                            Valider le retrait
                        </button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <section v-for="section in detailSections" :key="section.title" class="rounded-xl border border-slate-200">
                            <h4 class="border-b border-slate-100 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ section.title }}</h4>
                            <dl>
                                <div v-for="fact in section.items" :key="fact.label" class="flex items-baseline justify-between gap-4 border-b border-slate-100 px-4 py-2.5 last:border-0">
                                    <dt class="text-sm text-slate-500">{{ fact.label }}</dt>
                                    <dd class="text-right text-sm font-medium" :class="fact.class || 'text-slate-900'">{{ fact.value }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>

                    <section v-if="filesTarget.observations" class="rounded-2xl border border-slate-200 px-4 py-3">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observations / Actions</h4>
                        <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-800">{{ filesTarget.observations }}</p>
                    </section>

                    <section>
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fichiers</h4>
                            <span class="flex items-center gap-2">
                                <span class="text-xs text-slate-400">{{ filesTarget.attachments?.length || 0 }}</span>
                                <button
                                    v-if="permissions.can_edit && !fileFormOpen && filesTarget.workflow_status !== 'pending_deletion'"
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-700 text-lg font-semibold leading-none text-white hover:bg-blue-800"
                                    @click="showFileForm"
                                >
                                    +
                                </button>
                            </span>
                        </div>
                        <p v-if="filesError" class="mt-3 text-sm text-red-600">{{ filesError }}</p>
                        <ul v-if="filesTarget.attachments?.length" class="mt-2 space-y-2">
                            <li v-for="file in filesTarget.attachments" :key="file.path" class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M7 3.5h7l5 5V20a1.5 1.5 0 0 1-1.5 1.5h-10.5A1.5 1.5 0 0 1 5.5 20V5A1.5 1.5 0 0 1 7 3.5Z" />
                                        <path d="M14 3.5V9h5.5" />
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ file.name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ file.original_name || file.name }}</span>
                                </span>
                                <span class="hidden rounded-full px-2 py-0.5 text-[11px] font-semibold sm:inline-flex" :class="rightClass(file.right)">{{ file.right || 'Lecture' }}</span>
                                <span class="flex shrink-0 items-center gap-1">
                                    <button v-if="canPreview(file)" type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50" @click="previewAttachment(file)">Voir</button>
                                    <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50" @click="downloadAttachment(file)">Télécharger</button>
                                    <button v-if="permissions.can_edit && filesTarget.workflow_status !== 'pending_deletion'" type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50" @click="removeFile(file)">Retirer</button>
                                </span>
                            </li>
                        </ul>
                        <p v-else class="mt-2 rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">Aucun fichier sur ce document.</p>

                        <form v-if="permissions.can_edit && fileFormOpen && filesTarget.workflow_status !== 'pending_deletion'" class="mt-3 space-y-2" @submit.prevent="uploadFiles">
                            <div v-for="item in pendingFiles" :key="item.key" class="grid gap-2 rounded-xl border border-dashed border-slate-300 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8.5rem_auto] sm:items-center">
                                <input v-model="item.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nom">
                                <label class="flex cursor-pointer items-center rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span class="truncate">{{ item.file ? item.file.name : 'Choisir un fichier' }}</span>
                                    <input class="sr-only" type="file" @change="onFilePicked(item, $event)">
                                </label>
                                <select v-model="item.right" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                    <option value="Lecture">Lecture</option>
                                    <option value="Modification">Modification</option>
                                    <option value="Confidentiel">Confidentiel</option>
                                </select>
                                <button type="button" class="text-sm font-medium text-red-700 hover:underline" @click="removePending(item.key)">Retirer</button>
                            </div>
                            <div class="flex items-center justify-between gap-3 pt-1">
                                <button type="button" class="text-sm font-medium text-blue-700 hover:underline" @click="addPendingRow">Ajouter une ligne</button>
                                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" :disabled="uploading">
                                    {{ uploading ? 'Envoi…' : 'Ajouter' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-slate-200 bg-white px-6 py-4">
                    <button type="button" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="filesTarget = null">Fermer</button>
                    <div class="flex gap-2">
                        <button
                            v-if="permissions.can_delete && filesTarget.workflow_status === 'pending_deletion'"
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            @click="cancelDeletion"
                        >
                            Annuler le retrait
                        </button>
                        <button
                            v-else-if="permissions.can_delete"
                            type="button"
                            class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700"
                            @click="askDelete(filesTarget)"
                        >
                            Retirer
                        </button>
                        <button
                            v-if="permissions.can_edit && filesTarget.workflow_status !== 'pending_deletion'"
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            @click="editFromDetail"
                        >
                            Modifier
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="deleteTarget" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/40 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900">{{ deleteMode === 'confirm' ? 'Valider le retrait ?' : 'Demander le retrait ?' }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ deleteTarget.reference }} — {{ deleteTarget.document_name }}</p>
                <p class="mt-2 text-sm text-slate-500">
                    {{ deleteMode === 'confirm'
                        ? 'Le document et ses fichiers seront supprimés.'
                        : 'Le document reste visible jusqu’à la validation du Responsable IT.' }}
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="deleteTarget = null">Annuler</button>
                    <button type="button" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" :disabled="deleting" @click="confirmDelete">
                        {{ deleting ? 'Envoi…' : (deleteMode === 'confirm' ? 'Valider le retrait' : 'Demander') }}
                    </button>
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
const selectedEnvironmentId = ref('');
const permissions = ref({ can_create: false, can_edit: false, can_delete: false, can_validate: false });
const validating = ref(false);
const statusFilter = ref('');
const formOpen = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const deleteMode = ref('request');
const deleting = ref(false);
const filesTarget = ref(null);
const filesError = ref('');
const pendingFiles = ref([]);
const formFiles = ref([]);
const existingFormFiles = ref([]);
const fileFormOpen = ref(false);
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

const detailSections = computed(() => {
    const row = filesTarget.value;
    if (!row) return [];

    return [
        {
            title: 'Revue',
            items: [
                { label: 'Date de création', value: formatDate(row.created_on) },
                { label: 'Dernière revue', value: formatDate(row.last_review_date) },
                { label: 'Périodicité', value: row.periodicity_months ? `${row.periodicity_months} mois` : '—' },
                { label: 'Prochaine revue', value: formatDate(row.next_review_date) },
                { label: 'Jours restants', value: formatDays(row.days_remaining), class: daysClass(row) },
            ],
        },
        {
            title: 'Document',
            items: [
                { label: 'Filiale', value: row.environment?.name || '—' },
                { label: 'Catégorie', value: row.category || '—' },
                { label: 'Direction / Service', value: row.direction || '—' },
                { label: 'Responsable', value: row.responsible || '—' },
                { label: 'Version', value: row.version || '—' },
                { label: 'Date de validation', value: formatDate(row.validated_on) },
                { label: 'Validé par', value: row.validated_by || '—' },
            ],
        },
    ];
});

const validationTone = computed(() => {
    if (filesTarget.value?.workflow_status === 'pending_deletion') {
        return { box: 'border-red-200 bg-red-50', title: 'text-red-800' };
    }
    if (filesTarget.value?.workflow_status === 'pending_validation') {
        return { box: 'border-orange-200 bg-orange-50', title: 'text-orange-800' };
    }
    return { box: 'border-emerald-200 bg-emerald-50', title: 'text-emerald-800' };
});

const validationSentence = computed(() => {
    const row = filesTarget.value;
    if (!row) return '';
    if (row.workflow_status === 'pending_deletion') {
        return 'Le retrait de ce document attend la validation du Responsable IT.';
    }
    if (row.workflow_status === 'pending_validation') {
        return 'Ce document attend la validation du Responsable IT.';
    }
    if (row.workflow_validator_name) {
        const when = formatDate(row.workflow_validated_at);
        return when === '—'
            ? `Validé par ${row.workflow_validator_name}.`
            : `Validé par ${row.workflow_validator_name} le ${when}.`;
    }
    return 'Document validé.';
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

function rightClass(right) {
    if (right === 'Confidentiel') return 'bg-amber-100 text-amber-800';
    if (right === 'Modification') return 'bg-blue-100 text-blue-800';
    return 'bg-slate-100 text-slate-700';
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
    selectedEnvironmentId.value = payload?.environment_id ? String(payload.environment_id) : '';
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
    existingFormFiles.value = [];
    formFiles.value = [blankFileRow()];
    formError.value = '';
    fillForm(null);
    formOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    existingFormFiles.value = (row.attachments || []).map((file) => ({
        path: file.path,
        name: file.name || '',
        original_name: file.original_name || file.name || '',
        right: file.right || 'Lecture',
    }));
    formFiles.value = [blankFileRow()];
    formError.value = '';
    fillForm(row);
    formOpen.value = true;
}

function removeExistingFile(path) {
    existingFormFiles.value = existingFormFiles.value.filter((item) => item.path !== path);
}

function addFormFile() {
    formFiles.value = [...formFiles.value, blankFileRow()];
}

function removeFormFile(key) {
    formFiles.value = formFiles.value.filter((item) => item.key !== key);
    if (!formFiles.value.length) {
        formFiles.value = [blankFileRow()];
    }
}

function askDelete(row) {
    deleteMode.value = 'request';
    deleteTarget.value = row;
}

function askConfirmDeletion() {
    if (!filesTarget.value) return;
    deleteMode.value = 'confirm';
    deleteTarget.value = filesTarget.value;
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
    fileFormOpen.value = false;
    pendingFiles.value = [];
    filesTarget.value = row;
}

function showFileForm() {
    if (!pendingFiles.value.length) {
        pendingFiles.value = [blankFileRow()];
    }
    fileFormOpen.value = true;
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
    const errorTarget = formOpen.value ? formError : filesError;
    if (file && file.size > 20 * 1024 * 1024) {
        errorTarget.value = 'Chaque fichier doit faire au plus 20 Mo.';
        event.target.value = '';
        return;
    }
    item.file = file;
    errorTarget.value = '';
    event.target.value = '';
}

function removePending(key) {
    pendingFiles.value = pendingFiles.value.filter((item) => item.key !== key);
    if (!pendingFiles.value.length) {
        fileFormOpen.value = false;
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
        pendingFiles.value = [];
        fileFormOpen.value = false;
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
    const ready = formFiles.value.filter((item) => item.file);
    if (ready.some((item) => !item.name.trim()) || existingFormFiles.value.some((item) => !item.name.trim())) {
        formError.value = 'Saisissez le nom de chaque fichier.';
        saving.value = false;
        return;
    }
    const payload = {
        ...form,
        reference: form.reference.trim() || null,
        periodicity_months: Number(form.periodicity_months) || 12,
        environment_id: selectedEnvironmentId.value,
    };
    if (editingId.value) {
        payload.sync_attachments = true;
        payload.kept_paths = existingFormFiles.value.map((item) => item.path);
        payload.kept_names = existingFormFiles.value.map((item) => item.name.trim());
        payload.kept_rights = existingFormFiles.value.map((item) => item.right);
    }
    try {
        if (ready.length) {
            const body = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((entry) => body.append(`${key}[]`, entry));
                    return;
                }
                if (value !== null && value !== undefined && value !== '') {
                    body.append(key, value === true ? '1' : value);
                }
            });
            ready.forEach((item) => {
                body.append('attachments[]', item.file);
                body.append('attachment_names[]', item.name.trim());
                body.append('attachment_rights[]', item.right);
            });
            if (editingId.value) {
                body.append('_method', 'PUT');
                await api.post(`/pci-documents/${editingId.value}`, body);
            } else {
                await api.post('/pci-documents', body);
            }
        } else if (editingId.value) {
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
    deleting.value = true;
    try {
        if (deleteMode.value === 'confirm') {
            await api.post(`/pci-documents/${target.id}/confirm-deletion`);
            if (filesTarget.value?.id === target.id) {
                filesTarget.value = null;
            }
        } else {
            const { data } = await api.delete(`/pci-documents/${target.id}`);
            const updated = data?.data ?? data;
            replaceRow(updated);
        }
        deleteTarget.value = null;
        await loadRows();
    } catch (err) {
        error.value = apiError(err, 'Retrait impossible.');
        deleteTarget.value = null;
    } finally {
        deleting.value = false;
    }
}

async function cancelDeletion() {
    if (!filesTarget.value) return;
    try {
        const { data } = await api.post(`/pci-documents/${filesTarget.value.id}/cancel-deletion`);
        replaceRow(data?.data ?? data);
        await loadRows();
    } catch (err) {
        filesError.value = apiError(err, 'Annulation du retrait impossible.');
    }
}

onMounted(loadRows);
</script>
