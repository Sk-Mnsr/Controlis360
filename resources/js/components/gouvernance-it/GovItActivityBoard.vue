<template>
    <div class="space-y-8">
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="success" class="text-sm text-emerald-700">{{ success }}</p>

        <p v-if="!ensembles.length && !loading" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
            Aucun ensemble pour le moment. Cliquez sur le <strong>+</strong> en haut à droite pour en créer un.
        </p>

        <div
            v-for="ensemble in ensembles"
            :id="`ensemble-${ensemble.id}`"
            :key="ensemble.id"
            class="space-y-5 rounded-2xl border border-slate-200 bg-slate-50/60 p-4"
        >
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-800">{{ ensemble.label }}</h3>
                <button
                    type="button"
                    class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                    title="Supprimer cet ensemble"
                    :disabled="ensemble.deleting"
                    @click="deleteEnsemble(ensemble)"
                >
                    {{ ensemble.deleting ? 'Suppression…' : 'Supprimer l’ensemble ×' }}
                </button>
            </div>

            <section
                v-for="section in visibleSections"
                :key="`${ensemble.id}-${section.key}`"
                class="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between gap-3 bg-[#a3181f] px-3 py-2 text-white">
                    <h4 class="text-sm font-bold uppercase tracking-wide">{{ section.label }}</h4>
                    <button
                        v-if="canManageSection(section.key)"
                        type="button"
                        class="text-2xl font-light leading-none text-sky-300 transition hover:text-white"
                        title="Ajouter une ligne"
                        @click="addRow(ensemble, section.key)"
                    >
                        +
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1200px] w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-left text-slate-700">
                                <th class="border border-slate-300 px-2 py-2 font-semibold">N°</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">{{ section.label }}</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">Impact <span class="text-red-600">*</span></th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">Owner</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">Priorité</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">Statut</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">DATE DE LIVRAISON</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">START DATE (EFFECTIVE)</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">FINISH DATE (EFFECTIVE)</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">LEAD TIME</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">Commentaire</th>
                                <th class="border border-slate-300 px-2 py-2 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!sectionRows(ensemble, section.key).length">
                                <td colspan="12" class="border border-slate-200 px-3 py-4 text-center text-slate-400">
                                    Aucune ligne — cliquez sur + pour en ajouter.
                                </td>
                            </tr>
                            <tr
                                v-for="(row, index) in sectionRows(ensemble, section.key)"
                                :key="row.localKey"
                                :class="[
                                    index % 2 === 0 ? 'bg-white' : 'bg-slate-50',
                                    row.validation_status === 'pending' ? '!bg-amber-50' : '',
                                ]"
                            >
                                <td class="border border-slate-300 px-2 py-1.5 text-center font-medium">{{ index + 1 }}</td>

                                <!-- Titre -->
                                <td class="border border-slate-300 px-2 py-1.5">
                                    <input
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.title"
                                        type="text"
                                        class="w-full min-w-[160px] rounded border border-slate-300 bg-white px-1.5 py-1 outline-none focus:border-sky-400"
                                        :ref="(el) => setTitleRef(row.localKey, el)"
                                    />
                                    <span v-else class="font-medium text-slate-800">{{ row.title || '—' }}</span>
                                </td>

                                <!-- Impact (3e position, obligatoire) -->
                                <td class="border border-slate-300 px-2 py-1.5 max-w-[160px]">
                                    <textarea
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.impact"
                                        rows="2"
                                        required
                                        class="w-full min-w-[120px] resize-y rounded border border-slate-300 bg-white px-1 py-1 outline-none focus:border-sky-400"
                                        placeholder="Impact *"
                                    />
                                    <span v-else class="line-clamp-2 text-slate-700">{{ row.impact || '—' }}</span>
                                </td>

                                <!-- Owner -->
                                <td
                                    class="border border-slate-300 px-1 py-1"
                                    :class="isOwnerOfRow(row) ? 'bg-[#d4a017] font-semibold text-slate-900' : ''"
                                >
                                    <select
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.owner"
                                        class="w-full min-w-[110px] rounded border border-slate-300 px-1 py-1 outline-none focus:border-sky-400"
                                        :class="isOwnerOfRow(row) ? 'bg-[#d4a017]' : 'bg-white'"
                                    >
                                        <option value="">—</option>
                                        <option v-for="owner in owners" :key="owner" :value="owner">{{ owner }}</option>
                                    </select>
                                    <span v-else>{{ row.owner || '—' }}</span>
                                </td>

                                <!-- Priorité -->
                                <td class="border border-slate-300 px-1 py-1">
                                    <select
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.priorite"
                                        class="w-full min-w-[70px] rounded border border-slate-300 bg-white px-1 py-1 font-semibold outline-none focus:border-sky-400"
                                    >
                                        <option value="">—</option>
                                        <option value="P1">P1</option>
                                        <option value="P2">P2</option>
                                        <option value="P3">P3</option>
                                    </select>
                                    <span v-else class="font-semibold">{{ row.priorite || '—' }}</span>
                                </td>

                                <!-- Statut -->
                                <td class="border border-slate-300 px-1 py-1.5 text-center">
                                    <select
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.statut"
                                        class="rounded px-2 py-1 font-bold text-white"
                                        :class="row.statut === 'OPEN' ? 'bg-red-500' : 'bg-emerald-500'"
                                    >
                                        <option value="OPEN">OPEN</option>
                                        <option value="CLOSE">CLOSE</option>
                                    </select>
                                    <span
                                        v-else
                                        class="inline-block rounded px-2 py-0.5 font-bold text-white"
                                        :class="row.statut === 'OPEN' ? 'bg-red-500' : 'bg-emerald-500'"
                                    >
                                        {{ row.statut }}
                                    </span>
                                </td>

                                <!-- Dates -->
                                <td class="border border-slate-300 px-1 py-1">
                                    <input
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.date_livraison"
                                        type="date"
                                        class="w-full rounded border border-slate-300 bg-white px-1 py-1 outline-none focus:border-sky-400"
                                    />
                                    <span v-else class="whitespace-nowrap">{{ formatDate(row.date_livraison) }}</span>
                                </td>
                                <td class="border border-slate-300 px-1 py-1">
                                    <input
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.start_date"
                                        type="date"
                                        class="w-full rounded border border-slate-300 bg-white px-1 py-1 outline-none focus:border-sky-400"
                                        @change="refreshLeadTime(row)"
                                    />
                                    <span v-else class="whitespace-nowrap">{{ formatDate(row.start_date) }}</span>
                                </td>
                                <td class="border border-slate-300 px-1 py-1">
                                    <input
                                        v-if="row.editing && canManageRow(section.key, row)"
                                        v-model="row.finish_date"
                                        type="date"
                                        class="w-full rounded border border-slate-300 bg-white px-1 py-1 outline-none focus:border-sky-400"
                                        @change="onFinishDateChange(row)"
                                    />
                                    <span v-else class="whitespace-nowrap">{{ formatDate(row.finish_date) }}</span>
                                </td>

                                <td class="border border-slate-300 px-2 py-1.5 text-center font-medium">
                                    {{ row.lead_time_days ?? '—' }}
                                </td>

                                <!-- Commentaire (champ ligne — distinct du chat Actions) -->
                                <td class="border border-slate-300 px-2 py-1.5 min-w-[140px] max-w-[200px]">
                                    <div class="flex items-start gap-1.5">
                                        <button
                                            type="button"
                                            class="mt-0.5 shrink-0 text-sky-600 hover:text-sky-800"
                                            title="Commentaire (champ ligne)"
                                            @click="openCommentFieldModal(row, section.key)"
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
                                            <p class="line-clamp-2 text-slate-700">{{ row.commentaire || '—' }}</p>
                                            <button
                                                v-if="row.commentaire"
                                                type="button"
                                                class="mt-0.5 text-[10px] italic text-sky-600 hover:underline"
                                                @click="openCommentFieldModal(row, section.key)"
                                            >
                                                Voir plus
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="border border-slate-300 px-2 py-1.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <button
                                            type="button"
                                            class="relative inline-flex h-7 w-7 items-center justify-center rounded border border-sky-300 text-sky-600 hover:bg-sky-50 disabled:opacity-40"
                                            title="Discussion (chat) sur toute la ligne"
                                            :disabled="!row.id"
                                            @click="openChatModal(row)"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M18 10c0 3.866-3.582 7-8 7a8.84 8.84 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <span
                                                v-if="row.messages_count > 0"
                                                class="absolute -right-1 -top-1 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-sky-600 px-0.5 text-[8px] font-bold text-white"
                                            >
                                                {{ row.messages_count > 9 ? '9+' : row.messages_count }}
                                            </span>
                                        </button>

                                        <button
                                            type="button"
                                            class="relative inline-flex h-7 w-7 items-center justify-center rounded border border-slate-300 text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                                            title="Pièces jointes"
                                            :disabled="!row.id"
                                            @click="openAttachmentsModal(row)"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <span
                                                v-if="row.attachments_count > 0"
                                                class="absolute -right-1 -top-1 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-slate-700 px-0.5 text-[8px] font-bold text-white"
                                            >
                                                {{ row.attachments_count > 9 ? '9+' : row.attachments_count }}
                                            </span>
                                        </button>

                                        <template v-if="canManageRow(section.key, row)">
                                            <button
                                                v-if="!row.editing"
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-sky-300 text-sky-600 hover:bg-sky-50"
                                                title="Modifier"
                                                @click="startEdit(row)"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                v-else
                                                type="button"
                                                class="inline-flex h-7 items-center justify-center rounded border border-emerald-400 bg-emerald-50 px-2 text-[10px] font-semibold text-emerald-800 hover:bg-emerald-100 disabled:opacity-50"
                                                title="Enregistrer"
                                                :disabled="row.saving || row.sending || row.deleting || row.submitting || row.validating"
                                                @click="saveRow(row)"
                                            >
                                                {{ row.saving ? '…' : 'Save' }}
                                            </button>

                                            <button
                                                v-if="canManageSection(section.key) || isOwnerOfRow(row)"
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-red-300 text-red-600 hover:bg-red-50 disabled:opacity-50"
                                                title="Supprimer"
                                                :disabled="row.saving || row.sending || row.deleting || row.submitting || row.validating"
                                                @click="deleteRow(ensemble, section.key, row)"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>

                                            <span
                                                v-if="row.validation_status === 'pending'"
                                                class="inline-flex h-7 items-center rounded border border-amber-300 bg-amber-50 px-2 text-[10px] font-semibold text-amber-800"
                                            >
                                                En attente
                                            </span>

                                            <button
                                                v-if="canValidateRow(row)"
                                                type="button"
                                                class="inline-flex h-7 items-center justify-center rounded border border-emerald-500 bg-emerald-600 px-2 text-[10px] font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                                title="Valider la ligne Agent IT"
                                                :disabled="row.saving || row.sending || row.deleting || row.submitting || row.validating"
                                                @click="validateRow(row)"
                                            >
                                                {{ row.validating ? '…' : 'Valider' }}
                                            </button>

                                            <button
                                                v-if="canSubmitValidation(section.key, row)"
                                                type="button"
                                                class="inline-flex h-7 items-center justify-center rounded border border-amber-400 bg-amber-50 px-2 text-[10px] font-semibold text-amber-900 hover:bg-amber-100 disabled:opacity-50"
                                                title="Soumettre au Responsable IT pour validation"
                                                :disabled="row.saving || row.sending || row.deleting || row.submitting || row.validating || !row.id"
                                                @click="submitValidation(row)"
                                            >
                                                {{ row.submitting ? '…' : 'Soumettre' }}
                                            </button>

                                            <button
                                                v-if="canSendRow(section.key, row)"
                                                type="button"
                                                class="inline-flex h-7 items-center justify-center rounded border border-sky-400 bg-sky-50 px-2 text-[10px] font-semibold text-sky-800 hover:bg-sky-100 disabled:opacity-50"
                                                title="Envoyer au Responsable Régional"
                                                :disabled="row.saving || row.sending || row.deleting || row.submitting || row.validating || !row.id"
                                                @click="sendRow(row)"
                                            >
                                                {{ row.sending ? '…' : 'Send' }}
                                            </button>
                                        </template>

                                        <template v-else-if="row.locked">
                                            <span
                                                class="inline-flex h-7 items-center rounded border border-slate-300 bg-slate-100 px-2.5 text-[10px] font-semibold text-slate-600"
                                            >
                                                Envoyé
                                            </span>
                                        </template>

                                        <template v-else-if="row.validation_status === 'pending'">
                                            <span
                                                class="inline-flex h-7 items-center rounded border border-amber-300 bg-amber-50 px-2 text-[10px] font-semibold text-amber-800"
                                            >
                                                En attente
                                            </span>
                                            <button
                                                v-if="canValidateRow(row)"
                                                type="button"
                                                class="inline-flex h-7 items-center justify-center rounded border border-emerald-500 bg-emerald-600 px-2 text-[10px] font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                                title="Valider la ligne Agent IT"
                                                :disabled="row.validating"
                                                @click="validateRow(row)"
                                            >
                                                {{ row.validating ? '…' : 'Valider' }}
                                            </button>
                                        </template>

                                        <span
                                            v-else
                                            class="inline-flex h-7 items-center rounded border border-slate-200 bg-slate-50 px-2 text-[10px] text-slate-400"
                                        >
                                            Lecture seule
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Modal champ Commentaire (colonne) -->
        <div
            v-if="commentFieldModal.open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeCommentFieldModal"
        >
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Commentaire</h3>
                    <button
                        type="button"
                        class="rounded px-2 text-lg leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        @click="closeCommentFieldModal"
                    >
                        ×
                    </button>
                </div>
                <div class="p-4">
                    <textarea
                        v-model="commentFieldModal.draft"
                        rows="6"
                        class="w-full resize-y rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-400"
                        :readonly="!commentFieldModal.canEdit"
                        placeholder="Saisir un commentaire…"
                    />
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-4 py-3">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        @click="closeCommentFieldModal"
                    >
                        {{ commentFieldModal.canEdit ? 'Annuler' : 'Fermer' }}
                    </button>
                    <button
                        v-if="commentFieldModal.canEdit"
                        type="button"
                        class="rounded-lg bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800 disabled:opacity-50"
                        :disabled="commentFieldModal.saving"
                        @click="saveCommentFieldModal"
                    >
                        {{ commentFieldModal.saving ? '…' : 'Enregistrer' }}
                    </button>
                </div>
            </div>
        </div>

        <GovItActivityChatModal
            :open="chatModal.open"
            :activity-id="chatModal.activityId"
            :activity-title="chatModal.activityTitle"
            @close="closeChatModal"
            @updated="onChatUpdated"
        />

        <GovItActivityAttachmentsModal
            :open="attachmentsModal.open"
            :activity-id="attachmentsModal.activityId"
            :activity-title="attachmentsModal.activityTitle"
            :initial-attachments="attachmentsModal.attachments"
            :can-edit="attachmentsModal.canEdit"
            @close="closeAttachmentsModal"
            @updated="onAttachmentsUpdated"
        />
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import GovItActivityChatModal from './GovItActivityChatModal.vue';
import GovItActivityAttachmentsModal from './GovItActivityAttachmentsModal.vue';

const props = defineProps({
    moduleSlug: {
        type: String,
        required: true,
    },
    owners: {
        type: Array,
        default: () => [],
    },
});

const auth = useAuthStore();

const SECTION_DEFS = [
    { key: 'projets_en_cours', label: 'Projets en cours' },
    { key: 'chantiers_en_cours', label: 'Chantiers en cours' },
    { key: 'chantier_migration_si', label: "Chantier Système d'Information Flexcube (SI)" },
    { key: 'incidents', label: 'INCIDENTS' },
    { key: 'points_attention', label: "Points d'Attention" },
];

const isAgentIt = computed(() => auth.user?.profile === 'agent_it');
const isResponsableIt = computed(() =>
    ['responsable_it', 'super_admin', 'admin'].includes(auth.user?.profile),
);

const visibleSections = computed(() => SECTION_DEFS);

const ensembles = ref([]);
const loading = ref(true);
const error = ref('');
const success = ref('');
const titleRefs = {};
let localSeq = 0;

const commentFieldModal = reactive({
    open: false,
    row: null,
    sectionKey: '',
    draft: '',
    canEdit: false,
    saving: false,
});

const chatModal = reactive({
    open: false,
    activityId: null,
    activityTitle: '',
});

const attachmentsModal = reactive({
    open: false,
    activityId: null,
    activityTitle: '',
    attachments: [],
    canEdit: false,
    rowRef: null,
});

const owners = computed(() => props.owners);

function canManageSection(sectionKey) {
    if (isAgentIt.value) {
        return sectionKey === 'points_attention';
    }
    return true;
}

function isOwnerOfRow(row) {
    const name = auth.user?.name?.trim();
    if (!name || !row?.owner) {
        return false;
    }
    return name.toLowerCase() === String(row.owner).trim().toLowerCase();
}

function canManageRow(sectionKey, row) {
    if (row?.locked) {
        return false;
    }
    return canManageSection(sectionKey) || isOwnerOfRow(row);
}

function sectionRequiresValidation(sectionKey) {
    return sectionKey !== 'points_attention';
}

function canSubmitValidation(sectionKey, row) {
    if (!isAgentIt.value || row.locked || !sectionRequiresValidation(sectionKey)) {
        return false;
    }
    if (row.validation_status === 'pending' || row.validation_status === 'validated') {
        return false;
    }
    return canManageRow(sectionKey, row);
}

function canValidateRow(row) {
    if (!isResponsableIt.value || row.locked) {
        return false;
    }
    return row.validation_status === 'pending';
}

function canSendRow(sectionKey, row) {
    if (row.locked || !canManageRow(sectionKey, row)) {
        return false;
    }
    // Agent IT : Send direct uniquement sur Points d'Attention, sinon après validation.
    if (isAgentIt.value && sectionRequiresValidation(sectionKey)) {
        return row.validation_status === 'validated';
    }
    // Responsable IT / admin : peuvent envoyer (sauf si encore en attente sans valider — ils peuvent aussi valider puis send).
    if (row.validation_status === 'pending' && !isResponsableIt.value) {
        return false;
    }
    return true;
}

function emptySections() {
    const sections = {};
    SECTION_DEFS.forEach((section) => {
        sections[section.key] = [];
    });
    return sections;
}

function formatDate(value) {
    if (!value) return '—';
    const parts = String(value).slice(0, 10).split('-');
    if (parts.length !== 3) return value;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function mapServerRow(row, { editing = false } = {}) {
    localSeq += 1;
    return {
        localKey: `db-${row.id}-${localSeq}`,
        id: row.id,
        ensemble_id: row.ensemble_id,
        section: row.section,
        title: row.title ?? '',
        owner: row.owner ?? '',
        priorite: row.priorite ?? '',
        statut: row.statut ?? 'OPEN',
        date_livraison: row.date_livraison ?? '',
        start_date: row.start_date ?? '',
        finish_date: row.finish_date ?? '',
        lead_time_days: row.lead_time_days,
        impact: row.impact ?? '',
        commentaire: row.commentaire ?? '',
        attachments: row.attachments ?? [],
        attachments_count: Number(row.attachments_count ?? (row.attachments?.length ?? 0)),
        messages_count: Number(row.messages_count ?? 0),
        workflow_status: row.workflow_status,
        validation_status: row.validation_status ?? null,
        requires_validation: Boolean(row.requires_validation ?? (row.section !== 'points_attention')),
        locked: Boolean(row.locked),
        editing,
        saving: false,
        sending: false,
        submitting: false,
        validating: false,
        deleting: false,
    };
}

function mapEnsemble(payload) {
    const sections = emptySections();
    SECTION_DEFS.forEach((section) => {
        sections[section.key] = (payload.sections?.[section.key] ?? []).map((row) => mapServerRow(row));
    });

    return {
        id: payload.id,
        label: payload.label,
        module_slug: payload.module_slug,
        sections,
        deleting: false,
    };
}

function sectionRows(ensemble, sectionKey) {
    return ensemble.sections[sectionKey] || [];
}

function setTitleRef(key, el) {
    if (el) titleRefs[key] = el;
}

function refreshLeadTime(row) {
    if (!row.start_date || !row.finish_date) {
        row.lead_time_days = null;
        return;
    }
    const start = new Date(row.start_date);
    const finish = new Date(row.finish_date);
    if (Number.isNaN(start.getTime()) || Number.isNaN(finish.getTime()) || finish < start) {
        row.lead_time_days = null;
        return;
    }
    row.lead_time_days = Math.round((finish - start) / (1000 * 60 * 60 * 24));
}

function requireImpact(row) {
    if (!String(row.impact ?? '').trim()) {
        error.value = 'Le champ Impact est obligatoire.';
        return false;
    }
    return true;
}

function onFinishDateChange(row) {
    if (row.finish_date) {
        row.statut = 'CLOSE';
    }
    refreshLeadTime(row);
}

function extractError(err, fallback) {
    const apiErrors = Object.values(err.response?.data?.errors ?? {}).flat().join(' ');
    return err.response?.data?.message || apiErrors || fallback;
}

async function startEdit(row) {
    row.editing = true;
    await nextTick();
    titleRefs[row.localKey]?.focus?.();
}

function openCommentFieldModal(row, sectionKey) {
    commentFieldModal.open = true;
    commentFieldModal.row = row;
    commentFieldModal.sectionKey = sectionKey;
    commentFieldModal.draft = row.commentaire ?? '';
    commentFieldModal.canEdit = canManageRow(sectionKey, row);
    commentFieldModal.saving = false;
}

function closeCommentFieldModal() {
    commentFieldModal.open = false;
    commentFieldModal.row = null;
    commentFieldModal.sectionKey = '';
    commentFieldModal.draft = '';
    commentFieldModal.canEdit = false;
    commentFieldModal.saving = false;
}

async function saveCommentFieldModal() {
    const row = commentFieldModal.row;
    if (!row || !commentFieldModal.canEdit) return;

    commentFieldModal.saving = true;
    row.commentaire = commentFieldModal.draft;
    try {
        await saveRow(row, { keepEditing: row.editing, silent: true });
        closeCommentFieldModal();
    } catch {
        // error already set in saveRow
    } finally {
        commentFieldModal.saving = false;
    }
}

function openChatModal(row) {
    if (!row?.id) return;
    chatModal.open = true;
    chatModal.activityId = row.id;
    chatModal.activityTitle = row.title || '';
}

function closeChatModal() {
    chatModal.open = false;
    chatModal.activityId = null;
    chatModal.activityTitle = '';
}

function onChatUpdated({ activityId, count }) {
    ensembles.value.forEach((ensemble) => {
        Object.values(ensemble.sections || {}).forEach((rows) => {
            rows.forEach((row) => {
                if (Number(row.id) === Number(activityId)) {
                    row.messages_count = count;
                }
            });
        });
    });
}

function openAttachmentsModal(row) {
    if (!row?.id) return;
    attachmentsModal.open = true;
    attachmentsModal.activityId = row.id;
    attachmentsModal.activityTitle = row.title || '';
    attachmentsModal.attachments = [...(row.attachments || [])];
    attachmentsModal.canEdit = true;
    attachmentsModal.rowRef = row;
}

function closeAttachmentsModal() {
    attachmentsModal.open = false;
    attachmentsModal.activityId = null;
    attachmentsModal.activityTitle = '';
    attachmentsModal.attachments = [];
    attachmentsModal.canEdit = false;
    attachmentsModal.rowRef = null;
}

function onAttachmentsUpdated(payload) {
    const row = attachmentsModal.rowRef;
    if (!row) return;
    row.attachments = payload.attachments ?? [];
    row.attachments_count = Number(payload.attachments_count ?? row.attachments.length);
    attachmentsModal.attachments = [...row.attachments];
}

async function loadEnsembles() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/gouvernance-it/ensembles', {
            params: { module_slug: props.moduleSlug },
        });
        const list = data.data ?? data ?? [];
        ensembles.value = (Array.isArray(list) ? list : []).map(mapEnsemble);
    } catch (err) {
        error.value = extractError(err, 'Impossible de charger les ensembles.');
    } finally {
        loading.value = false;
    }
}

async function addEnsemble() {
    error.value = '';
    success.value = '';
    try {
        const { data } = await api.post('/gouvernance-it/ensembles', {
            module_slug: props.moduleSlug,
        });
        const created = mapEnsemble(data.data ?? data);
        ensembles.value.unshift(created);
        success.value = `Ensemble créé : ${created.label}`;
        await nextTick();
        document.getElementById(`ensemble-${created.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (err) {
        error.value = extractError(err, 'Impossible de créer l’ensemble.');
    }
}

async function deleteEnsemble(ensemble) {
    const confirmed = window.confirm(
        `Supprimer « ${ensemble.label} » et toutes ses lignes ?\nCette action est irréversible.`,
    );
    if (!confirmed) {
        return;
    }

    error.value = '';
    success.value = '';
    ensemble.deleting = true;

    try {
        await api.delete(`/gouvernance-it/ensembles/${ensemble.id}`);
        ensembles.value = ensembles.value.filter((item) => item.id !== ensemble.id);
        success.value = 'Ensemble supprimé.';
    } catch (err) {
        error.value = extractError(err, 'Impossible de supprimer l’ensemble.');
        ensemble.deleting = false;
    }
}

async function addRow(ensemble, sectionKey) {
    if (!canManageSection(sectionKey)) {
        error.value = 'L’Agent IT ne peut créer que des Points d’Attention.';
        return;
    }

    error.value = '';
    success.value = '';
    try {
        const { data } = await api.post('/gouvernance-it/activities', {
            ensemble_id: ensemble.id,
            module_slug: props.moduleSlug,
            section: sectionKey,
            statut: 'OPEN',
        });
        const created = mapServerRow(data.data ?? data, { editing: true });
        ensemble.sections[sectionKey].push(created);
        await nextTick();
        titleRefs[created.localKey]?.focus?.();
    } catch (err) {
        error.value = extractError(err, 'Impossible d’ajouter la ligne.');
    }
}

async function saveRow(row, { keepEditing = false, silent = false } = {}) {
    if (!requireImpact(row)) {
        throw new Error('Impact obligatoire');
    }

    error.value = '';
    if (!silent) success.value = '';
    row.saving = true;
    if (row.finish_date) {
        row.statut = 'CLOSE';
    }
    refreshLeadTime(row);

    const payload = {
        ensemble_id: row.ensemble_id,
        module_slug: props.moduleSlug,
        section: row.section,
        title: row.title,
        owner: row.owner,
        priorite: row.priorite || null,
        statut: row.statut,
        date_livraison: row.date_livraison || null,
        start_date: row.start_date || null,
        finish_date: row.finish_date || null,
        impact: row.impact,
        commentaire: row.commentaire,
    };

    try {
        const { data } = await api.put(`/gouvernance-it/activities/${row.id}`, payload);
        const saved = mapServerRow(data.data ?? data, { editing: keepEditing });
        Object.assign(row, saved, {
            localKey: row.localKey,
            editing: keepEditing,
            saving: false,
            sending: false,
            submitting: false,
            validating: false,
            deleting: false,
        });
        if (!silent) {
            success.value = 'Ligne enregistrée.';
        }
    } catch (err) {
        error.value = extractError(err, 'Erreur lors de l’enregistrement.');
        throw err;
    } finally {
        row.saving = false;
    }
}

async function submitValidation(row) {
    if (!row.id) {
        error.value = 'Enregistrez la ligne (Save) avant de la soumettre.';
        return;
    }
    if (!requireImpact(row)) {
        return;
    }

    error.value = '';
    success.value = '';
    row.submitting = true;

    try {
        if (row.editing) {
            await saveRow(row, { keepEditing: false, silent: true });
        }
        const { data } = await api.post(`/gouvernance-it/activities/${row.id}/submit-validation`);
        const updated = mapServerRow(data.data ?? data);
        Object.assign(row, updated, {
            localKey: row.localKey,
            editing: false,
            saving: false,
            sending: false,
            submitting: false,
            validating: false,
            deleting: false,
        });
        success.value = 'Ligne soumise au Responsable IT pour validation.';
    } catch (err) {
        error.value = extractError(err, 'Erreur lors de la soumission.');
    } finally {
        row.submitting = false;
    }
}

async function validateRow(row) {
    if (!row.id) return;

    error.value = '';
    success.value = '';
    row.validating = true;

    try {
        const { data } = await api.post(`/gouvernance-it/activities/${row.id}/validate`);
        const updated = mapServerRow(data.data ?? data);
        Object.assign(row, updated, {
            localKey: row.localKey,
            editing: false,
            saving: false,
            sending: false,
            submitting: false,
            validating: false,
            deleting: false,
        });
        success.value = 'Ligne validée. Elle peut être envoyée au Responsable Régional.';
    } catch (err) {
        error.value = extractError(err, 'Erreur lors de la validation.');
    } finally {
        row.validating = false;
    }
}

async function sendRow(row) {
    if (!row.id) {
        error.value = 'Enregistrez la ligne (Save) avant de l’envoyer.';
        return;
    }
    if (!requireImpact(row)) {
        return;
    }

    error.value = '';
    success.value = '';
    row.sending = true;

    try {
        // Ne pas ré-enregistrer systématiquement : un Save Agent IT annulerait la validation.
        if (row.editing) {
            await saveRow(row, { keepEditing: false, silent: true });
        }
        const { data } = await api.post(`/gouvernance-it/activities/${row.id}/send`);
        const sent = mapServerRow(data.data ?? data);
        Object.assign(row, sent, {
            localKey: row.localKey,
            editing: false,
            saving: false,
            sending: false,
            submitting: false,
            validating: false,
            deleting: false,
        });
        success.value = 'Ligne envoyée au Responsable Régional.';
    } catch (err) {
        error.value = extractError(err, 'Erreur lors de l’envoi.');
    } finally {
        row.sending = false;
    }
}

async function deleteRow(ensemble, sectionKey, row) {
    if (row.locked) return;

    error.value = '';
    success.value = '';
    row.deleting = true;

    try {
        if (row.id) {
            await api.delete(`/gouvernance-it/activities/${row.id}`);
        }
        ensemble.sections[sectionKey] = ensemble.sections[sectionKey].filter((item) => item.localKey !== row.localKey);
    } catch (err) {
        error.value = extractError(err, 'Impossible de supprimer la ligne.');
        row.deleting = false;
    }
}

defineExpose({ addEnsemble });

onMounted(loadEnsembles);
</script>
