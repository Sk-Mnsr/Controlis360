<template>
    <section class="flex min-h-full flex-1 flex-col bg-white">
        <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-emerald-50 to-white px-6 py-5 lg:px-8">
            <RouterLink :to="backToMissionRoute" class="text-sm font-medium text-slate-500 hover:text-slate-800">
                ← Retour à la mission
            </RouterLink>
        </div>

        <div v-if="loading" class="flex flex-1 items-center justify-center p-10 text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="loadError" class="flex flex-1 items-center justify-center p-10 text-sm text-red-600">
            {{ loadError }}
        </div>

        <div v-else-if="recommendation" class="flex flex-1 flex-col">
            <div class="flex-1 overflow-y-auto px-6 py-6 lg:px-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ mission?.reference }}
                        </p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">
                            Détail — {{ recommendation.reference }}
                        </h1>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                        <button
                            v-if="recommendation.can_edit"
                            type="button"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                            @click="goEdit"
                        >
                            Modifier
                        </button>
                        <button
                            v-if="recommendation.can_delete"
                            type="button"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            @click="confirmDelete"
                        >
                            Supprimer
                        </button>
                        <RouterLink
                            :to="backToMissionRoute"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        >
                            Fermer
                        </RouterLink>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-emerald-800">Informations</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li>
                            <span class="text-slate-600">Référence :</span>
                            <span class="font-bold text-slate-900">{{ displayValue(recommendation.reference) }}</span>
                        </li>
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
                        <li v-if="recommendation.regulator_transmitted_at">
                            <span class="text-slate-600">Transmis au régulateur :</span>
                            <span class="font-bold text-slate-900">
                                {{ formatDateTime(recommendation.regulator_transmitted_at) }}
                                <template v-if="recommendation.regulator_transmitted_by_name">
                                    — {{ recommendation.regulator_transmitted_by_name }}
                                </template>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="mt-6 space-y-5">
                    <div v-if="recommendation.theme" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Thème</h3>
                        <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ recommendation.theme }}</p>
                    </div>

                    <div v-if="recommendation.risk_type" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Risque</h3>
                        <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ recommendation.risk_type }}</p>
                    </div>

                    <div v-if="recommendation.recommendation_label" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Recommandation</h3>
                        <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ recommendation.recommendation_label }}</p>
                    </div>

                    <div v-if="recommendation.comments" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Commentaires</h3>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-slate-800">{{ recommendation.comments }}</p>
                    </div>

                    <MissionRecoRegulatorCommentsPanel
                        v-if="showTransmissionComments"
                        variant="transmission"
                        title="Transmissions au régulateur"
                        :comments="recommendation.regulator_transmission_comments ?? []"
                        :show-empty="false"
                    />

                    <MissionRecoRegulatorCommentsPanel
                        v-if="showRegulatorComments"
                        :comments="regulatorAvisComments"
                        hint="Consultez cet avis avant de clôturer ou non la recommandation."
                    />

                    <div v-if="recommendation.attachment_paths?.length" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Pièces jointes</h3>
                        <ul class="mt-2 space-y-1 text-sm text-slate-800">
                            <li v-for="path in recommendation.attachment_paths" :key="path">
                                {{ fileName(path) }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import MissionRecoRegulatorCommentsPanel from '../../components/audit/MissionRecoRegulatorCommentsPanel.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const mission = ref(null);
const recommendation = ref(null);
const loading = ref(true);
const loadError = ref('');

const backToMissionRoute = computed(() => ({
    name: 'audit.missions.show',
    params: { id: route.params.id },
    query: {
        from: route.query.from,
        dept_id: route.query.dept_id,
    },
}));

const showRegulatorComments = computed(() => {
    if (recommendation.value?.can_view_regulator_comments) return true;

    const profile = auth.user?.profile;

    return ['controle', 'audit', 'super_admin'].includes(profile)
        && recommendation.value?.regulator_transmitted_at;
});

const regulatorAvisComments = computed(() => (
    recommendation.value?.regulator_avis_comments
    ?? (recommendation.value?.regulator_comments ?? []).filter(
        (item) => (item.comment_type ?? 'avis') === 'avis',
    )
));

const showTransmissionComments = computed(() => {
    const transmissions = recommendation.value?.regulator_transmission_comments ?? [];
    return transmissions.length > 0 && showRegulatorComments.value;
});

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

function displayValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    return value;
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function fileName(path) {
    if (!path) return '—';
    return String(path).split('/').pop();
}

async function loadData() {
    loading.value = true;
    loadError.value = '';

    try {
        const [missionRes, recoRes] = await Promise.all([
            api.get(`/missions/${route.params.id}`),
            api.get(`/recommendations/${route.params.recoId}`),
        ]);

        mission.value = missionRes.data?.data ?? missionRes.data;
        recommendation.value = recoRes.data?.data ?? recoRes.data;
    } catch {
        loadError.value = 'Impossible de charger le détail de la recommandation.';
    } finally {
        loading.value = false;
    }
}

function goEdit() {
    if (!recommendation.value?.id) return;

    router.push({
        name: 'audit.missions.recommendation.edit',
        params: { id: route.params.id, recoId: recommendation.value.id },
        query: {
            from: route.query.from,
            dept_id: route.query.dept_id,
        },
    });
}

async function confirmDelete() {
    if (!recommendation.value?.id) return;
    if (!window.confirm(`Supprimer la recommandation ${recommendation.value.reference} ?`)) return;

    try {
        await api.delete(`/recommendations/${recommendation.value.id}`);
        router.push(backToMissionRoute.value);
    } catch (err) {
        loadError.value = err.response?.data?.message?.[0] ?? 'Suppression impossible.';
    }
}

onMounted(loadData);
</script>
