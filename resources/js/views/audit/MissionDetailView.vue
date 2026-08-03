<template>
    <section class="flex min-h-full flex-1 flex-col bg-white">
        <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-emerald-50 to-white px-6 py-5 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <RouterLink :to="backRoute" class="text-sm font-medium text-slate-500 hover:text-slate-800">
                    ← {{ backLabel }}
                </RouterLink>

                <div v-if="mission" class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="mission.my_response_fr"
                        class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700"
                    >
                        Réponse : {{ mission.my_response_fr }}
                    </span>
                    <button
                        v-if="mission.can_add_recommendation"
                        type="button"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                        @click="goCreateRecommendation"
                    >
                        + Reco
                    </button>
                    <RouterLink
                        :to="backRoute"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        Fermer
                    </RouterLink>
                </div>
            </div>
        </div>

        <div v-if="loading" class="flex flex-1 items-center justify-center p-10 text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="loadError" class="flex flex-1 items-center justify-center p-10 text-sm text-red-600">
            {{ loadError }}
        </div>

        <div v-else-if="mission" class="flex flex-1 flex-col">
            <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6 lg:px-8">
                <header class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-bold tracking-tight text-slate-900">
                                    {{ mission.reference }}
                                </h1>
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    {{ mission.status_fr ?? mission.status }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ mission.mission_type_fr ?? mission.mission_type }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                {{ mission.period || periodLabel }}
                                <span class="mx-1.5 text-slate-300">·</span>
                                {{ mission.environment_label ?? environmentLabel }}
                            </p>
                        </div>
                    </div>
                </header>

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.9fr)] xl:items-start">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-4 text-xs font-bold uppercase tracking-wide text-slate-500">
                            Résumé de la mission
                        </h2>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Auditeur</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ mission.auditor || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Créée par</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ mission.created_by_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Période</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ mission.period || periodLabel }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Environnement</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ mission.environment_label ?? environmentLabel }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-slate-500">Entité(s) mission</dt>
                                <dd class="mt-1.5 flex flex-wrap gap-2">
                                    <template v-if="missionEntities.length">
                                        <span
                                            v-for="entity in missionEntities"
                                            :key="entity.id"
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800"
                                        >
                                            {{ entity.name }}
                                        </span>
                                    </template>
                                    <span v-else class="text-sm font-semibold text-slate-900">—</span>
                                </dd>
                            </div>
                        </dl>

                        <div v-if="reportAttachments.length || mission.report_reference" class="mt-5 border-t border-slate-100 pt-4">
                            <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                                Rapports associés
                            </h3>
                            <ul v-if="reportAttachments.length" class="space-y-2">
                                <li
                                    v-for="file in reportAttachments"
                                    :key="file.path"
                                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5"
                                >
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-red-100 text-[10px] font-bold uppercase text-red-700">
                                            PDF
                                        </span>
                                        <span class="min-w-0 truncate text-sm font-medium text-slate-800" :title="file.name">
                                            {{ file.name }}
                                        </span>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-2">
                                        <button
                                            v-if="canPreviewReport(file.path)"
                                            type="button"
                                            class="text-xs font-semibold text-blue-700 hover:text-blue-900 disabled:opacity-60"
                                            :disabled="busyAttachmentPath === file.path"
                                            @click="previewReport(file)"
                                        >
                                            {{ busyAttachmentPath === file.path && busyAttachmentAction === 'preview' ? 'Ouverture…' : 'Visualiser' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 disabled:opacity-60"
                                            :disabled="busyAttachmentPath === file.path"
                                            @click="downloadReport(file)"
                                        >
                                            {{ busyAttachmentPath === file.path && busyAttachmentAction === 'download' ? 'Téléchargement…' : 'Télécharger' }}
                                        </button>
                                    </span>
                                </li>
                            </ul>
                            <p
                                v-else-if="mission.report_reference"
                                class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
                            >
                                {{ mission.report_reference }}
                            </p>
                            <p v-if="attachmentError" class="mt-1.5 text-xs text-red-600">{{ attachmentError }}</p>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between gap-2">
                            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Avancement des recommandations
                            </h2>
                            <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-bold text-white">
                                {{ panelSummaryStats?.implementation_rate ?? 0 }}%
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <article class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ panelSummaryStats?.total ?? 0 }}</p>
                            </article>
                            <article class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Implémentées</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-900">{{ panelSummaryStats?.implemented ?? 0 }}</p>
                            </article>
                            <article class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">En cours</p>
                                <p class="mt-1 text-2xl font-bold text-amber-900">{{ panelSummaryStats?.in_progress ?? 0 }}</p>
                            </article>
                            <article class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">No start</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ panelSummaryStats?.no_start ?? 0 }}</p>
                            </article>
                        </div>

                        <div class="mt-4">
                            <div class="mb-1.5 flex items-center justify-between text-xs text-slate-500">
                                <span>Taux d'implémentation</span>
                                <span class="font-semibold text-slate-800">{{ panelSummaryStats?.implementation_rate ?? 0 }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-emerald-600 transition-all"
                                    :style="{ width: `${Math.min(100, panelSummaryStats?.implementation_rate ?? 0)}%` }"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">
                                Recommandations par département
                            </h2>
                            <span
                                v-if="visibleRecommendations.length"
                                class="inline-flex min-w-[1.5rem] items-center justify-center rounded bg-red-500 px-1.5 py-0.5 text-xs font-semibold text-white"
                            >
                                {{ visibleRecommendations.length }}
                            </span>
                        </div>
                    </div>

                    <div v-if="!departmentStats.length" class="py-10 text-center text-sm text-slate-500">
                        Aucune recommandation pour cette mission.
                    </div>

                    <div v-else class="flex min-h-[280px]">
                        <div class="flex w-44 shrink-0 flex-col border-r border-slate-200 sm:w-52">
                            <div class="border border-white/20 bg-red-800 px-4 py-3 text-sm font-semibold text-white">
                                Entité
                            </div>
                            <div class="flex-1 bg-slate-50">
                                <button
                                    v-for="dept in departmentStats"
                                    :key="dept.id"
                                    type="button"
                                    class="flex w-full flex-col items-start gap-1 border-b border-slate-200 px-4 py-4 text-left transition last:border-b-0"
                                    :class="isListDepartmentSelected(dept)
                                        ? 'bg-emerald-700 text-white'
                                        : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900'"
                                    @click="selectListDepartment(dept)"
                                >
                                    <span class="text-sm font-semibold leading-tight">{{ dept.name }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs"
                                        :class="isListDepartmentSelected(dept) ? 'bg-emerald-600 text-white' : 'bg-white text-slate-500'"
                                    >
                                        {{ dept.total }} reco{{ dept.total > 1 ? 's' : '' }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <template v-if="selectedListDepartment">
                                <div v-if="listDepartmentRecommendations.length" class="overflow-x-auto">
                                    <table class="min-w-full border-collapse text-sm">
                                        <thead>
                                            <tr class="bg-red-800 text-left text-white">
                                                <th class="border border-white/20 px-4 py-3 font-semibold">Référence</th>
                                                <th class="border border-white/20 px-4 py-3 font-semibold">Recommandation</th>
                                                <th
                                                    v-if="!isOwner"
                                                    class="border border-white/20 px-4 py-3 font-semibold"
                                                >
                                                    OWNERS
                                                </th>
                                                <th class="border border-white/20 px-4 py-3 font-semibold">Échéance</th>
                                                <th class="border border-white/20 px-4 py-3 font-semibold">Jours restants</th>
                                                <th class="border border-white/20 px-4 py-3 font-semibold">Statut</th>
                                                <th class="border border-white/20 px-4 py-3 font-semibold text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template
                                                v-for="(reco, index) in listDepartmentRecommendations"
                                                :key="reco.id"
                                            >
                                                <tr :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-50'">
                                                    <td class="border border-white px-4 py-3 font-medium text-slate-900">
                                                        {{ reco.reference }}
                                                    </td>
                                                    <td class="max-w-[16rem] border border-white px-4 py-3 text-slate-800">
                                                        <span class="line-clamp-2">{{ recoRecommendationLabel(reco) }}</span>
                                                    </td>
                                                    <td
                                                        v-if="!isOwner"
                                                        class="border border-white px-4 py-3 text-slate-800"
                                                    >
                                                        {{ reco.responsible_name || '—' }}
                                                    </td>
                                                    <td class="border border-white px-4 py-3 whitespace-nowrap text-slate-800">
                                                        {{ formatDate(reco.due_date) }}
                                                    </td>
                                                    <td class="border border-white px-4 py-3">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span
                                                                v-if="recoDaysRemaining(reco) !== null"
                                                                class="text-sm font-semibold"
                                                                :style="remainingDaysTextStyle(recoDaysRemaining(reco))"
                                                            >
                                                                {{ recoDaysRemaining(reco) }}
                                                            </span>
                                                            <span v-else class="text-slate-500">—</span>
                                                            <span
                                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                                                :style="deadlineToneStyle(recoDeadlineStatus(reco).tone)"
                                                            >
                                                                {{ recoDeadlineStatus(reco).label }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="border border-white px-4 py-3">
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                            {{ reco.status_fr ?? reco.status ?? '—' }}
                                                        </span>
                                                    </td>
                                                    <td class="border border-white px-4 py-3 text-center">
                                                        <div class="inline-flex items-center justify-center gap-1">
                                                            <button
                                                                type="button"
                                                                class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"
                                                                @click="openRecoView(reco)"
                                                            >
                                                                Voir
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition"
                                                                :class="isRecoResponseExpanded(reco)
                                                                    ? 'bg-emerald-700 text-white'
                                                                    : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                                                                :title="isRecoResponseExpanded(reco) ? 'Masquer le détail' : 'Action'"
                                                                :aria-expanded="isRecoResponseExpanded(reco)"
                                                                @click="toggleRecoResponse(reco)"
                                                            >
                                                                <span class="sr-only">Action</span>
                                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                    <circle cx="10" cy="4" r="1.5" />
                                                                    <circle cx="10" cy="10" r="1.5" />
                                                                    <circle cx="10" cy="16" r="1.5" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr v-if="isRecoResponseExpanded(reco)">
                                                    <td
                                                        :colspan="recoTableColspan"
                                                        class="border border-white bg-emerald-50/20 px-4 py-4"
                                                    >
                                                        <MissionRecoOwnerActionsPanel
                                                            :reco="reco"
                                                            :mission="mission"
                                                            :response="actionResponseForReco(reco)"
                                                            :can-edit-action="canEditRecoAction"
                                                            :can-assign-action="isOwner"
                                                            @updated="refreshMissionData"
                                                        />
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                <template v-else>
                                    <div class="border border-white/20 bg-red-800 px-4 py-3 text-sm font-semibold text-white">
                                        &nbsp;
                                    </div>
                                    <p class="flex items-center justify-center p-6 text-sm text-slate-500">
                                        Aucune recommandation pour ce département.
                                    </p>
                                </template>
                            </template>
                            <template v-else>
                                <div class="border border-white/20 bg-red-800 px-4 py-3 text-sm font-semibold text-white">
                                    &nbsp;
                                </div>
                                <p class="flex h-full items-center justify-center p-6 text-sm text-slate-500">
                                    Cliquez sur un département pour afficher ses recommandations.
                                </p>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-if="mission.comments" class="rounded-xl border border-slate-200 p-4">
                    <h2 class="text-sm font-semibold text-slate-900">Commentaires mission</h2>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ mission.comments }}</p>
                </div>
            </div>
        </div>

        <MissionRecommendationViewModal
            :open="recoViewOpen"
            :reco-id="selectedRecoId"
            :mission-reference="mission?.reference ?? ''"
            @close="closeRecoView"
            @closed="onRecoClosed"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import MissionRecoOwnerActionsPanel from '../../components/audit/MissionRecoOwnerActionsPanel.vue';
import MissionRecommendationViewModal from '../../components/audit/MissionRecommendationViewModal.vue';
import { isMissionAgent, isMissionResponsible } from '../../config/module-access';
import { useAuthStore } from '../../stores/auth';
import { useMissionParametrage } from '../../composables/useMissionParametrage';
import {
    buildDepartmentStatsFromRecommendations,
    buildMissionStatsFromRecommendations,
    defaultDepartmentIdForRecommendations,
    ownerDepartmentsForReco,
    recommendationsForDepartment,
    recommendationsForOwner,
} from '../../utils/reco-stats';
import {
    attachmentFileName,
    downloadAttachment,
    isPreviewableAttachment,
    previewAttachment,
} from '../../utils/attachments';
import {
    recommendationRemainingDays,
} from '../../utils/mission-progress';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const {
    loadMissionParametrage,
    remainingDaysTextStyle,
    deadlineToneStyle,
    resolveDeadlineStatus,
} = useMissionParametrage();

const mission = ref(null);
const loading = ref(true);
const loadError = ref('');
const listDepartmentId = ref(null);
const expandedResponseRecoId = ref(null);
const recoViewOpen = ref(false);
const selectedRecoId = ref(null);
const busyAttachmentPath = ref('');
const busyAttachmentAction = ref('');
const attachmentError = ref('');

const recoTableColspan = computed(() => (isOwner.value ? 6 : 7));

const isOwner = computed(() => isMissionResponsible(auth.user));
const isAgent = computed(() => isMissionAgent(auth.user));

const ownerEntityIds = computed(() => (
    (auth.user?.entity_ids ?? []).map((id) => Number(id)).filter(Boolean)
));

const displayedRecommendations = computed(() => {
    if (!mission.value) return [];
    if (mission.value.recommendations?.length) return mission.value.recommendations;
    return mission.value.recommendation ? [mission.value.recommendation] : [];
});

const ownerRecommendations = computed(() => (
    recommendationsForOwner(displayedRecommendations.value, ownerEntityIds.value)
));

const visibleRecommendations = computed(() => (
    isOwner.value ? ownerRecommendations.value : displayedRecommendations.value
));

const departmentStats = computed(() => (
    buildDepartmentStatsFromRecommendations(
        visibleRecommendations.value,
        isOwner.value ? ownerEntityIds.value : null,
    )
));

const backRoute = computed(() => {
    if (route.query.from === 'dashboard') {
        return { name: 'audit.dashboard' };
    }
    if (route.query.from === 'missions') {
        return { name: 'audit.missions' };
    }
    if (route.query.type) {
        return {
            name: 'audit.missions.history.byType',
            params: { missionType: route.query.type },
        };
    }
    return { name: 'audit.missions.history' };
});

const backLabel = computed(() => {
    if (route.query.from === 'dashboard') return 'Retour au dashboard';
    if (route.query.from === 'missions') return 'Retour aux missions';
    return 'Retour à l\'historique';
});

const missionEntities = computed(() => {
    const missionEntityById = new Map(
        (mission.value?.entities ?? []).map((entity) => [String(entity.id), entity]),
    );

    const normalize = (entity) => {
        const fromMission = missionEntityById.get(String(entity.id));
        const name = entity.name ?? fromMission?.name ?? null;
        if (!name && !entity.id) return null;

        return {
            id: entity.id ?? name,
            name: name || '—',
        };
    };

    if (isOwner.value) {
        const byId = new Map();

        for (const department of ownerRecommendations.value
            .flatMap((reco) => ownerDepartmentsForReco(reco, ownerEntityIds.value))) {
            const normalized = normalize(department);
            if (normalized && !byId.has(String(normalized.id))) {
                byId.set(String(normalized.id), normalized);
            }
        }

        return [...byId.values()];
    }

    return (mission.value?.entities ?? [])
        .map((entity) => normalize(entity))
        .filter(Boolean);
});

const environmentLabel = computed(() => {
    const names = (mission.value?.entities ?? [])
        .map((e) => e.environment_name)
        .filter(Boolean);
    return [...new Set(names)].join(', ') || '—';
});

const periodLabel = computed(() => {
    const start = formatDate(mission.value?.start_date);
    const end = formatDate(mission.value?.end_date);
    return `${start} — ${end}`;
});

const displayedResponses = computed(() => {
    if (!mission.value) return [];

    let responses = [];
    if (mission.value.all_responses?.length) responses = mission.value.all_responses;
    else if (mission.value.mission_response) responses = [mission.value.mission_response];
    else responses = mission.value.responses ?? [];

    if (isOwner.value) {
        return responses.filter((response) => (
            response.responsable_id === auth.user?.id
            || response.responsable?.id === auth.user?.id
        ));
    }

    return responses;
});

const ownerActionResponse = computed(() => {
    const response = mission.value?.mission_response;
    if (response?.response_type === 'action') return response;

    return displayedResponses.value.find((item) => item.response_type === 'action') ?? null;
});

const activeActionResponse = computed(() => {
    if (isOwner.value || isAgent.value) {
        return ownerActionResponse.value;
    }

    return null;
});

const canEditRecoAction = computed(() => {
    const response = activeActionResponse.value;

    if (isAgent.value) {
        return Boolean(response?.can_edit);
    }

    if (isOwner.value) {
        return true;
    }

    return false;
});

const selectedListDepartment = computed(() => (
    departmentStats.value.find((dept) => String(dept.id) === String(listDepartmentId.value)) ?? null
));

const listDepartmentRecommendations = computed(() => {
    if (!selectedListDepartment.value) return [];
    return recommendationsForDepartment(visibleRecommendations.value, selectedListDepartment.value.id);
});

const panelSummaryStats = computed(() => {
    if (!mission.value) return null;

    const stats = buildMissionStatsFromRecommendations(visibleRecommendations.value);
    const ownerRecos = ownerRecommendations.value;

    return {
        label: isOwner.value
            ? (ownerRecos.length === 1
                ? ownerRecos[0].reference
                : ownerRecos.map((reco) => reco.reference).join(', ') || mission.value.reference)
            : mission.value.reference,
        total: stats.total ?? 0,
        implemented: stats.implemented ?? 0,
        in_progress: stats.in_progress ?? 0,
        no_start: stats.no_start ?? 0,
        implementation_rate: stats.implementation_rate ?? 0,
    };
});

const reportAttachments = computed(() => {
    const paths = mission.value?.report_attachment_paths ?? [];
    if (!Array.isArray(paths) || !paths.length) {
        return [];
    }

    const originalNames = String(mission.value?.report_reference ?? '')
        .split(',')
        .map((name) => name.trim())
        .filter(Boolean);

    return paths.map((path, index) => ({
        path,
        name: originalNames[index] || attachmentFileName(path),
    }));
});

function canPreviewReport(path) {
    return isPreviewableAttachment(path);
}

async function previewReport(file) {
    attachmentError.value = '';
    busyAttachmentPath.value = file.path;
    busyAttachmentAction.value = 'preview';

    try {
        await previewAttachment(file.path);
    } catch {
        attachmentError.value = 'Impossible d\'ouvrir ce fichier.';
    } finally {
        busyAttachmentPath.value = '';
        busyAttachmentAction.value = '';
    }
}

async function downloadReport(file) {
    attachmentError.value = '';
    busyAttachmentPath.value = file.path;
    busyAttachmentAction.value = 'download';

    try {
        await downloadAttachment(file.path, file.name);
    } catch {
        attachmentError.value = 'Impossible de télécharger ce fichier.';
    } finally {
        busyAttachmentPath.value = '';
        busyAttachmentAction.value = '';
    }
}

function isListDepartmentSelected(dept) {
    return String(dept.id) === String(listDepartmentId.value);
}

function selectListDepartment(dept) {
    listDepartmentId.value = dept.id;
    expandedResponseRecoId.value = null;
}

function recoRecommendationLabel(reco) {
    return reco.recommendation_label || reco.name || reco.theme || '—';
}

function recoDaysRemaining(reco) {
    return recommendationRemainingDays(reco);
}

function recoDeadlineStatus(reco) {
    return resolveDeadlineStatus(recommendationRemainingDays(reco));
}

function isRecoResponseExpanded(reco) {
    return String(expandedResponseRecoId.value) === String(reco.id);
}

function toggleRecoResponse(reco) {
    expandedResponseRecoId.value = isRecoResponseExpanded(reco) ? null : reco.id;
}

function openRecoView(reco) {
    selectedRecoId.value = reco.id;
    recoViewOpen.value = true;
}

function closeRecoView() {
    recoViewOpen.value = false;
    selectedRecoId.value = null;
}

async function onRecoClosed() {
    await refreshMissionData();
}

function ownerNamesForReco(reco) {
    return String(reco.responsible_name ?? '')
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean);
}

function responsesForReco(reco) {
    const responses = displayedResponses.value;

    if (isOwner.value) {
        const isOwnerReco = ownerRecommendations.value.some((item) => String(item.id) === String(reco.id));
        return isOwnerReco ? responses : [];
    }

    const ownerNames = ownerNamesForReco(reco);

    return responses.filter((response) => (
        ownerNames.includes(response.responsible_name)
        || ownerNames.includes(response.responsable?.name)
    ));
}

function actionResponseForReco(reco) {
    if (activeActionResponse.value) {
        return activeActionResponse.value;
    }

    return responsesForReco(reco).find((response) => response.response_type === 'action') ?? null;
}

function syncListDepartmentSelection() {
    const departments = departmentStats.value;
    if (!departments.length) {
        listDepartmentId.value = null;
        return;
    }

    const hasCurrent = departments.some((dept) => String(dept.id) === String(listDepartmentId.value));
    if (hasCurrent) return;

    const queryDeptId = route.query.dept_id;
    const fromQuery = queryDeptId
        ? departments.find((dept) => String(dept.id) === String(queryDeptId))
        : null;

    if (fromQuery) {
        listDepartmentId.value = fromQuery.id;
        return;
    }

    const defaultDeptId = defaultDepartmentIdForRecommendations(
        visibleRecommendations.value,
        isOwner.value ? ownerEntityIds.value : null,
    );

    if (defaultDeptId != null) {
        const defaultDept = departments.find((dept) => String(dept.id) === String(defaultDeptId));
        if (defaultDept) {
            listDepartmentId.value = defaultDept.id;
            return;
        }
    }

    listDepartmentId.value = departments[0].id;
}

function goRecoDetail(reco) {
    if (!mission.value?.id || !reco?.id) return;

    router.push({
        name: 'audit.missions.recommendation.show',
        params: { id: mission.value.id, recoId: reco.id },
        query: {
            from: route.query.from,
            dept_id: listDepartmentId.value,
        },
    });
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

async function loadMission(options = {}) {
    const silent = Boolean(options.silent);

    if (!silent) {
        loading.value = true;
        mission.value = null;
    }

    loadError.value = '';

    try {
        const { data } = await api.get(`/missions/${route.params.id}`);
        mission.value = data?.data ?? data;
        syncListDepartmentSelection();
    } catch {
        if (!silent) {
            loadError.value = 'Impossible de charger le détail de la mission.';
        }
    } finally {
        if (!silent) {
            loading.value = false;
        }
    }
}

async function refreshMissionData() {
    await loadMission({ silent: true });
}

function goCreateRecommendation() {
    if (!mission.value?.id) return;
    router.push({
        name: 'audit.missions.recommendation.create',
        params: { id: mission.value.id },
        query: { from: route.query.from },
    });
}

async function validateResponse(response) {
    if (!mission.value?.id || !window.confirm('Valider cette réponse et clôturer les recommandations ?')) return;

    try {
        await api.post(`/missions/${mission.value.id}/responses/${response.id}/validate`);
        await loadMission({ silent: true });
    } catch (err) {
        loadError.value = err.response?.data?.message?.[0] ?? 'Validation impossible.';
    }
}

watch(departmentStats, syncListDepartmentSelection, { immediate: true });

onMounted(async () => {
    await loadMissionParametrage();
    await loadMission();
});
</script>
