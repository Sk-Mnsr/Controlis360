<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <!-- En-tête -->
            <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-emerald-50 to-white px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Demande</p>
                        <h3 class="mt-1 truncate text-xl font-bold text-slate-900">{{ mission?.reference }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ mission?.title }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-slate-700 shadow-sm">
                                {{ mission?.status_fr ?? mission?.status }}
                            </span>
                            <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-slate-600 shadow-sm">
                                {{ mission?.mission_type_fr }}
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-700"
                        @click="$emit('close')"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex-1 p-10 text-center text-sm text-slate-500">Chargement...</div>
            <div v-else-if="loadError" class="flex-1 p-10 text-center text-sm text-red-600">{{ loadError }}</div>

            <div v-else-if="mission" class="flex-1 overflow-y-auto">
                <div class="grid gap-4 p-6 lg:grid-cols-2">
                    <!-- Colonne mission -->
                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <h4 class="text-sm font-semibold text-slate-900">Informations mission</h4>
                            <dl class="mt-3 space-y-2.5 text-sm">
                                <DetailRow label="Auditeur" :value="mission.auditor" />
                                <DetailRow label="Créée par" :value="mission.created_by_name" />
                                <DetailRow label="Date début" :value="formatDate(mission.start_date)" />
                                <DetailRow label="Date fin" :value="formatDate(mission.end_date)" />
                                <DetailRow label="Entité(s)" :value="entityNames" />
                            </dl>
                        </div>

                        <div v-if="mission.report_reference || mission.report_attachment_paths?.length" class="rounded-xl border border-slate-200 p-4">
                            <h4 class="text-sm font-semibold text-slate-900">Rapport associé</h4>
                            <p v-if="mission.report_reference" class="mt-2 whitespace-pre-wrap text-sm text-slate-700">
                                {{ mission.report_reference }}
                            </p>
                            <ul v-if="mission.report_attachment_paths?.length" class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="path in mission.report_attachment_paths" :key="path">
                                    {{ fileName(path) }}
                                </li>
                            </ul>
                        </div>

                        <div v-if="mission.comments" class="rounded-xl border border-slate-200 p-4">
                            <h4 class="text-sm font-semibold text-slate-900">Commentaires</h4>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ mission.comments }}</p>
                        </div>
                    </div>

                    <!-- Colonne recommandations -->
                    <div class="space-y-4">
                        <div
                            v-for="(reco, index) in displayedRecommendations"
                            :key="reco.id ?? index"
                            class="rounded-xl border border-slate-200 p-4"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-sm font-semibold text-slate-900">
                                    Recommandation{{ displayedRecommendations.length > 1 ? ` ${index + 1}` : '' }}
                                </h4>
                                <span
                                    v-if="reco.status_fr"
                                    class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700"
                                >
                                    {{ reco.status_fr }}
                                </span>
                            </div>
                            <dl class="mt-3 space-y-2.5 text-sm">
                                <DetailRow label="Référence" :value="reco.reference" />
                                <DetailRow label="Thème" :value="reco.theme" />
                                <DetailRow label="Libellé" :value="reco.recommendation_label" />
                                <DetailRow
                                    v-if="reco.recommendation_details"
                                    label="Détails"
                                    :value="reco.recommendation_details"
                                />
                                <DetailRow label="Date recommandation" :value="formatDate(reco.recommendation_date)" />
                                <DetailRow label="Échéance" :value="formatDate(reco.due_date)" />
                                <DetailRow label="Type de risque" :value="reco.risk_type" />
                                <DetailRow label="Niveau de risque" :value="reco.risk_level_fr" />
                                <DetailRow label="Priorité" :value="reco.priority_fr" />
                                <DetailRow label="Conserne" :value="reco.concerned_names" />
                                <DetailRow label="OWNERS" :value="reco.responsible_name" />
                                <DetailRow
                                    v-if="reco.comments"
                                    label="Commentaires"
                                    :value="reco.comments"
                                />
                            </dl>
                            <ul v-if="reco.attachment_paths?.length" class="mt-3 space-y-1 text-sm text-slate-700">
                                <li v-for="path in reco.attachment_paths" :key="path">
                                    {{ fileName(path) }}
                                </li>
                            </ul>
                        </div>

                        <div v-if="!displayedRecommendations.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                            Aucune recommandation pour cette mission.
                        </div>

                        <div
                            v-for="response in displayedResponses"
                            :key="response.id"
                            class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4"
                        >
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-slate-900">
                                    Réponse — {{ response.response_type_fr }}
                                </h4>
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs text-emerald-800">
                                    {{ response.workflow_status_fr }}
                                </span>
                            </div>

                            <template v-if="response.response_type === 'passivite'">
                                <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ response.passivity_comment }}</p>
                            </template>
                            <template v-else>
                                <dl class="mt-3 space-y-2 text-sm">
                                    <DetailRow label="OWNERS" :value="response.responsible_name" />
                                    <DetailRow label="Avancement" :value="progressLabel(response.progress_rate)" />
                                    <DetailRow label="Début" :value="formatDate(response.action_start_date)" />
                                    <DetailRow label="Fin prévue" :value="formatDate(response.planned_end_date)" />
                                    <DetailRow label="Go / No Go" :value="response.go_no_go_fr" />
                                    <DetailRow label="Investissement" :value="formatAmount(response.investment_amount)" />
                                    <DetailRow
                                        label="Infrastructure"
                                        :value="response.needs_infrastructure_change ? 'Oui' : 'Non'"
                                    />
                                    <DetailRow
                                        v-if="response.action_plan"
                                        label="Plan d'action"
                                        :value="response.action_plan"
                                    />
                                    <DetailRow
                                        v-if="response.comment"
                                        label="Commentaire"
                                        :value="response.comment"
                                    />
                                </dl>
                            </template>
                        </div>

                        <div v-if="displayedRecommendations.length && mission.recipients?.length" class="rounded-xl border border-slate-200 p-4">
                            <h4 class="text-sm font-semibold text-slate-900">Destinataires</h4>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="r in mission.recipients" :key="r.id">
                                    {{ r.name }}
                                    <span v-if="r.response_fr" class="text-slate-500">({{ r.response_fr }})</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied de page actions -->
            <div class="shrink-0 flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button
                    v-if="mission?.can_add_recommendation"
                    type="button"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                    @click="$emit('add-reco', mission)"
                >
                    + Reco
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    @click="$emit('close')"
                >
                    Fermer
                </button>
                <button
                    v-if="mission?.can_edit"
                    type="button"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                    @click="$emit('edit', mission)"
                >
                    Modifier
                </button>
                <button
                    v-if="mission?.can_delete"
                    type="button"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="$emit('delete', mission)"
                >
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import DetailRow from './DetailRow.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    missionId: { type: Number, default: null },
});

defineEmits(['close', 'edit', 'delete', 'add-reco']);

const mission = ref(null);
const loading = ref(false);
const loadError = ref('');

const entityNames = computed(() => {
    const names = (mission.value?.entities ?? []).map((e) => e.name).filter(Boolean);
    return names.length ? names.join(', ') : '—';
});

const displayedResponses = computed(() => {
    if (!mission.value) return [];
    if (mission.value.all_responses?.length) return mission.value.all_responses;
    if (mission.value.mission_response) return [mission.value.mission_response];
    return mission.value.responses ?? [];
});

const displayedRecommendations = computed(() => {
    if (!mission.value) return [];
    if (mission.value.recommendations?.length) return mission.value.recommendations;
    return mission.value.recommendation ? [mission.value.recommendation] : [];
});

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatAmount(value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${Number(value).toLocaleString('fr-FR')} FCFA`;
}

function progressLabel(value) {
    if (value === null || value === undefined) return '—';
    return `${value} %`;
}

function fileName(path) {
    if (!path) return '—';
    return String(path).split('/').pop();
}

async function loadMission() {
    if (!props.missionId) return;
    loading.value = true;
    loadError.value = '';
    mission.value = null;
    try {
        const { data } = await api.get(`/missions/${props.missionId}`);
        mission.value = data?.data ?? data;
    } catch {
        loadError.value = 'Impossible de charger le détail.';
    } finally {
        loading.value = false;
    }
}

watch(() => [props.open, props.missionId], ([isOpen, id]) => {
    if (isOpen && id) loadMission();
});
</script>
