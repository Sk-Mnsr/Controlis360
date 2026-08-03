<template>
    <div :class="embedded ? 'space-y-4' : 'space-y-6'">
        <section :class="embedded ? '' : 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm'">
            <div
                v-if="!embedded"
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">GovStrat IT-R</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Task ACTIVITY IT</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Sélectionnez une filiale pour voir les points qui la concernent,
                        organisés par origine (Projets, Chantiers, Incidents…).
                    </p>
                </div>
                <RouterLink
                    :to="{ name: 'gouvernance-it.govstrat-itr' }"
                    class="text-sm font-medium text-slate-500 hover:text-slate-800"
                >
                    ← Retour à GovStrat IT-R
                </RouterLink>
            </div>
            <div v-else class="mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Task ACTIVITY IT</h3>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">
                    Sélectionnez une filiale pour voir les points qui la concernent,
                    organisés par origine (Projets, Chantiers, Incidents…).
                </p>
            </div>

            <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

            <GovItWorkspaceHeader
                v-model="selectedEnvironmentId"
                :filiale="filialeLabel"
                :responsable="responsable"
                :team="team"
                :loading="loading"
                :show-add="false"
                :selectable="true"
                :filiales="filiales"
                @change="onFilialeChange"
            />

            <div v-if="loading" class="mt-6 text-sm text-slate-500">Chargement…</div>

            <div v-else class="mt-6 space-y-5">
                <section
                    v-for="section in sectionDefs"
                    :key="section.key"
                    class="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3 bg-[#a3181f] px-3 py-2 text-white">
                        <h3 class="text-sm font-bold uppercase tracking-wide">{{ section.label }}</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[1200px] w-full border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-left text-slate-700">
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">N°</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">{{ section.label }}</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Impact</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Origine</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Filiale</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Owner</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Priorité</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Statut</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">DATE DE LIVRAISON</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">START DATE</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">FINISH DATE</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">LEAD TIME</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Commentaire</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Motif</th>
                                    <th class="border border-slate-300 px-2 py-2 font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!sectionRows(section.key).length">
                                    <td colspan="15" class="border border-slate-200 px-3 py-4 text-center text-slate-400">
                                        Aucune ligne pour cette origine.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(row, index) in sectionRows(section.key)"
                                    :key="row.id"
                                    :class="[
                                        index % 2 === 0 ? 'bg-white' : 'bg-slate-50',
                                        row.is_attention ? 'bg-amber-50' : '',
                                        section.key === 'points_attention' ? 'bg-yellow-50' : '',
                                    ]"
                                >
                                    <td class="border border-slate-300 px-2 py-1.5 text-center font-medium">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 font-medium">
                                        {{ row.title || '—' }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 max-w-xs">
                                        {{ row.impact || '—' }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 whitespace-nowrap">
                                        {{ row.module_label }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5">{{ row.filiale }}</td>
                                    <td
                                        class="border border-slate-300 px-2 py-1.5"
                                        :class="isCurrentUserOwner(row) ? 'bg-[#d4a017] font-semibold text-slate-900' : ''"
                                    >
                                        {{ row.owner || '—' }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 text-center font-bold">
                                        <span
                                            class="rounded px-1.5 py-0.5"
                                            :class="{
                                                'bg-red-100 text-red-800': row.priorite === 'P1',
                                                'bg-amber-100 text-amber-800': row.priorite === 'P2',
                                                'bg-slate-100 text-slate-700': row.priorite === 'P3' || !row.priorite,
                                            }"
                                        >
                                            {{ row.priorite || '—' }}
                                        </span>
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 text-center">
                                        <span
                                            class="rounded px-1.5 py-0.5 font-bold"
                                            :class="row.statut === 'OPEN' ? 'bg-red-500 text-black' : 'bg-emerald-500 text-black'"
                                        >
                                            {{ row.statut }}
                                        </span>
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 whitespace-nowrap">
                                        {{ formatDate(row.date_livraison) }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 whitespace-nowrap">
                                        {{ formatDate(row.start_date) }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 whitespace-nowrap">
                                        {{ formatDate(row.finish_date) }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 text-center">
                                        {{ row.lead_time_days ?? '—' }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5 max-w-xs">
                                        {{ row.commentaire || '—' }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="reason in row.reception_reasons"
                                                :key="reason"
                                                class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700"
                                            >
                                                {{ reason }}
                                            </span>
                                            <span v-if="!row.reception_reasons?.length" class="text-slate-400">—</span>
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1.5">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <button
                                                type="button"
                                                class="relative inline-flex h-7 w-7 items-center justify-center rounded border border-sky-300 text-sky-600 hover:bg-sky-50"
                                                title="Discussion (chat) sur toute la ligne"
                                                @click="openChat(row)"
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
                                                class="relative inline-flex h-7 w-7 items-center justify-center rounded border border-slate-300 text-slate-600 hover:bg-slate-50"
                                                title="Pièces jointes"
                                                @click="openAttachments(row)"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                                <span
                                                    v-if="(row.attachments_count || row.attachments?.length) > 0"
                                                    class="absolute -right-1 -top-1 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-slate-700 px-0.5 text-[8px] font-bold text-white"
                                                >
                                                    {{ (row.attachments_count || row.attachments?.length) > 9 ? '9+' : (row.attachments_count || row.attachments?.length) }}
                                                </span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </section>

        <GovItActivityChatModal
            :open="chat.open"
            :activity-id="chat.activityId"
            :activity-title="chat.activityTitle"
            @close="closeChat"
            @updated="onChatUpdated"
        />

        <GovItActivityAttachmentsModal
            :open="attachments.open"
            :activity-id="attachments.activityId"
            :activity-title="attachments.activityTitle"
            :initial-attachments="attachments.list"
            :can-edit="true"
            @close="closeAttachments"
            @updated="onAttachmentsUpdated"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import GovItWorkspaceHeader from '../../components/gouvernance-it/GovItWorkspaceHeader.vue';
import GovItActivityChatModal from '../../components/gouvernance-it/GovItActivityChatModal.vue';
import GovItActivityAttachmentsModal from '../../components/gouvernance-it/GovItActivityAttachmentsModal.vue';

const props = defineProps({
    embedded: {
        type: Boolean,
        default: false,
    },
});

const SECTION_DEFS = [
    { key: 'projets_en_cours', label: 'Projets en cours' },
    { key: 'chantiers_en_cours', label: 'Chantiers en cours' },
    { key: 'chantier_migration_si', label: "Chantier Système d'Information Flexcube (SI)" },
    { key: 'incidents', label: 'INCIDENTS' },
    { key: 'points_attention', label: "Points d'Attention" },
];

const auth = useAuthStore();
const router = useRouter();

const loading = ref(true);
const error = ref('');
const filiales = ref([]);
const selectedEnvironmentId = ref(null);
const responsable = ref('—');
const team = ref('—');
const sections = ref({});

const chat = reactive({
    open: false,
    activityId: null,
    activityTitle: '',
});

const attachments = reactive({
    open: false,
    activityId: null,
    activityTitle: '',
    list: [],
    rowRef: null,
});

const sectionDefs = SECTION_DEFS;

const filialeLabel = computed(() => {
    const current = filiales.value.find((item) => Number(item.id) === Number(selectedEnvironmentId.value));
    return current?.name ?? '—';
});

function canAccess() {
    return ['super_admin', 'admin', 'responsable_regional'].includes(auth.user?.profile);
}

function sectionRows(sectionKey) {
    return sections.value[sectionKey] || [];
}

function isCurrentUserOwner(row) {
    const name = auth.user?.name?.trim();
    if (!name || !row?.owner) {
        return false;
    }
    return name.toLowerCase() === String(row.owner).trim().toLowerCase();
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = value.split('-');
    if (!y || !m || !d) return value;
    return `${d}/${m}/${y}`;
}

function openChat(row) {
    chat.open = true;
    chat.activityId = row.id;
    chat.activityTitle = row.title || '';
}

function closeChat() {
    chat.open = false;
    chat.activityId = null;
    chat.activityTitle = '';
}

function onChatUpdated({ activityId, count }) {
    Object.values(sections.value).forEach((rows) => {
        rows.forEach((row) => {
            if (Number(row.id) === Number(activityId)) {
                row.messages_count = count;
            }
        });
    });
}

function openAttachments(row) {
    attachments.open = true;
    attachments.activityId = row.id;
    attachments.activityTitle = row.title || '';
    attachments.list = [...(row.attachments || [])];
    attachments.rowRef = row;
}

function closeAttachments() {
    attachments.open = false;
    attachments.activityId = null;
    attachments.activityTitle = '';
    attachments.list = [];
    attachments.rowRef = null;
}

function onAttachmentsUpdated(payload) {
    const row = attachments.rowRef;
    if (!row) return;
    row.attachments = payload.attachments ?? [];
    row.attachments_count = Number(payload.attachments_count ?? row.attachments.length);
    attachments.list = [...row.attachments];
}

async function loadInbox(environmentId = null) {
    loading.value = true;
    error.value = '';
    try {
        const params = {};
        if (environmentId) {
            params.environment_id = environmentId;
        }

        const { data } = await api.get('/gouvernance-it/regional-inbox', { params });
        const payload = data.data ?? data ?? {};
        filiales.value = payload.filiales ?? [];
        selectedEnvironmentId.value = payload.environment_id ?? null;
        responsable.value = payload.responsable ?? '—';
        team.value = payload.team ?? '—';
        sections.value = payload.sections ?? {};
    } catch (err) {
        error.value = err.response?.data?.message
            || Object.values(err.response?.data?.errors ?? {}).flat().join(' ')
            || 'Impossible de charger Task ACTIVITY IT.';
    } finally {
        loading.value = false;
    }
}

async function onFilialeChange(environmentId) {
    if (!environmentId) {
        return;
    }
    await loadInbox(environmentId);
}

onMounted(async () => {
    if (!canAccess()) {
        if (!props.embedded) {
            router.replace({ name: 'gouvernance-it.govstrat-itr' });
        }
        return;
    }
    await loadInbox();
});
</script>
