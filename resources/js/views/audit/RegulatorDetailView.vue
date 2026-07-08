<template>
    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <RouterLink
                :to="{ name: 'audit.regulator' }"
                class="text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Retour à la file régulateur
            </RouterLink>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement…
        </div>

        <div v-else-if="loadError" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">
            {{ loadError }}
        </div>

        <template v-else-if="recommendation">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mission</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    {{ recommendation.mission_reference || '—' }}
                </h1>
                <ul class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                    <li>
                        <span class="text-slate-600">Type :</span>
                        <span class="font-semibold text-slate-900">{{ displayValue(recommendation.mission_type_fr) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Auditeur :</span>
                        <span class="font-semibold text-slate-900">{{ displayValue(recommendation.mission_auditor) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Date début :</span>
                        <span class="font-semibold text-slate-900">{{ formatDate(recommendation.mission_start_date) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Date fin :</span>
                        <span class="font-semibold text-slate-900">{{ formatDate(recommendation.mission_end_date) }}</span>
                    </li>
                    <li v-if="recommendation.regulator_transmitted_at" class="sm:col-span-2">
                        <span class="text-slate-600">Transmis le :</span>
                        <span class="font-semibold text-slate-900">
                            {{ formatDateTime(recommendation.regulator_transmitted_at) }}
                            <template v-if="recommendation.regulator_transmitted_by_name">
                                — {{ recommendation.regulator_transmitted_by_name }}
                            </template>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-emerald-800">
                    Recommandation — {{ recommendation.reference }}
                </h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li>
                        <span class="text-slate-600">Département(s) :</span>
                        <span class="font-bold text-slate-900">{{ displayValue(recommendation.entity_name) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Priorité :</span>
                        <span class="font-bold text-slate-900">{{ displayValue(recommendation.priority_fr) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Niveau de risque :</span>
                        <span class="font-bold text-slate-900">{{ displayValue(recommendation.risk_level_fr) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Statut :</span>
                        <span class="font-bold text-slate-900">{{ displayValue(recommendation.status_fr ?? recommendation.status) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">OWNERS :</span>
                        <span class="font-bold text-slate-900">{{ displayValue(recommendation.responsible_name) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Date recommandation :</span>
                        <span class="font-bold text-slate-900">{{ formatDate(recommendation.recommendation_date) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Échéance :</span>
                        <span class="font-bold text-slate-900">{{ formatDate(recommendation.due_date) }}</span>
                    </li>
                </ul>

                <div v-if="recommendation.theme" class="mt-5 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">Thème</h3>
                    <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ recommendation.theme }}</p>
                </div>

                <div v-if="recommendation.risk_type" class="mt-4 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">Risque</h3>
                    <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ recommendation.risk_type }}</p>
                </div>

                <div v-if="recommendation.recommendation_label" class="mt-4 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">Recommandation</h3>
                    <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ recommendation.recommendation_label }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Plans d'action</h2>
                <div class="mt-4">
                    <MissionRecoActionPlanSummary
                        :plans="recommendation.action_plans ?? []"
                        :reco-id="recommendation.id"
                        :can-comment="false"
                    />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Transmissions audit / contrôle</h2>
                <div class="mt-4">
                    <MissionRecoRegulatorCommentsPanel
                        variant="transmission"
                        title="Historique des transmissions"
                        :comments="recommendation.regulator_transmission_comments ?? []"
                        empty-label="Aucune transmission enregistrée."
                    />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Avis régulateur</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Répondez à l'audit ou au contrôle pour indiquer si la recommandation peut être clôturée ou non.
                </p>
                <div class="mt-4">
                    <MissionRecoRegulatorCommentForm
                        :reco="recommendation"
                        :comments="recommendation.regulator_avis_comments ?? []"
                        :can-comment="recommendation.can_comment_regulator"
                        @saved="reloadRecommendation"
                    />
                </div>
            </div>
        </template>
    </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import MissionRecoActionPlanSummary from '../../components/audit/MissionRecoActionPlanSummary.vue';
import MissionRecoRegulatorCommentForm from '../../components/audit/MissionRecoRegulatorCommentForm.vue';
import MissionRecoRegulatorCommentsPanel from '../../components/audit/MissionRecoRegulatorCommentsPanel.vue';

const route = useRoute();

const loading = ref(true);
const loadError = ref('');
const recommendation = ref(null);

function displayValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    return value;
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return formatDate(value);
    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function loadRecommendation() {
    const recoId = Number(route.params.recoId);
    if (!recoId) {
        loadError.value = 'Recommandation introuvable.';
        loading.value = false;
        return;
    }

    loading.value = true;
    loadError.value = '';

    try {
        const { data } = await api.get(`/recommendations/${recoId}`);
        recommendation.value = data?.data ?? data;
    } catch {
        loadError.value = 'Impossible de charger le détail de la recommandation.';
        recommendation.value = null;
    } finally {
        loading.value = false;
    }
}

async function reloadRecommendation() {
    const recoId = recommendation.value?.id;
    if (!recoId) return;

    try {
        const { data } = await api.get(`/recommendations/${recoId}`);
        recommendation.value = data?.data ?? data;
    } catch {
        // keep current data
    }
}

onMounted(loadRecommendation);

watch(() => route.params.recoId, loadRecommendation);
</script>
