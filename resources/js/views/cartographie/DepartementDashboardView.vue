<template>
    <div class="departement-dashboard-page">
        <div class="departement-dashboard-actions">
            <RouterLink
                :to="{
                    name: 'cartographie.departement-analyse',
                    params: { code: route.params.code },
                    query: environmentQueryParams(route),
                }"
                class="departement-dashboard-link"
            >
                ← Cartographie complète
            </RouterLink>
            <RouterLink
                :to="{
                    name: 'cartographie.departement-historique',
                    params: { code: route.params.code },
                    query: environmentQueryParams(route),
                }"
                class="departement-dashboard-secondary"
            >
                Historique
            </RouterLink>
        </div>

        <div v-if="loading" class="departement-dashboard-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !entity" class="departement-dashboard-error">{{ error }}</p>

            <template v-else>
                <header class="departement-dashboard-header">
                    <p class="departement-dashboard-kicker">Dashboard entité</p>
                    <h1 class="departement-dashboard-title">{{ entity?.name }}</h1>
                </header>

                <div class="departement-dashboard-metrics">
                    <article class="departement-dashboard-metric departement-dashboard-metric-gross">
                        <p class="departement-dashboard-metric-label">Évaluation intrinsèque</p>
                        <p class="departement-dashboard-metric-value">
                            {{ formatRiskScore(summaryAverages?.gross.risk) ?? '—' }}
                        </p>
                        <p class="departement-dashboard-metric-detail">
                            G {{ formatRiskScore(summaryAverages?.gross.gravity) ?? '—' }}
                            · P {{ formatRiskScore(summaryAverages?.gross.probability) ?? '—' }}
                        </p>
                    </article>

                    <article class="departement-dashboard-metric departement-dashboard-metric-residual">
                        <p class="departement-dashboard-metric-label">Évaluation résiduelle</p>
                        <p class="departement-dashboard-metric-value">
                            {{ formatRiskScore(summaryAverages?.residual.risk) ?? '—' }}
                        </p>
                        <p class="departement-dashboard-metric-detail">
                            G {{ formatRiskScore(summaryAverages?.residual.gravity) ?? '—' }}
                            · Pr {{ formatRiskScore(summaryAverages?.residual.probability) ?? '—' }}
                        </p>
                    </article>
                </div>

                <RiskDetailSummary
                    :rows="rows"
                    :categories="riskCategories"
                    :classifications="riskClassifications"
                />
            </template>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import { useCartographieStore } from '../../stores/cartographie';
import { environmentQueryParams } from '../../utils/entityEnvironment';
import { computeRiskAverages, formatRiskScore } from '../../utils/riskScore';
import RiskDetailSummary from '../../components/cartographie/RiskDetailSummary.vue';

const route = useRoute();
const cartographie = useCartographieStore();

const loading = ref(true);
const error = ref('');
const entity = ref(null);
const rows = ref([]);
const riskCategories = ref([]);
const riskClassifications = ref([]);

const summaryAverages = computed(() => computeRiskAverages(rows.value));

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        entity: root?.entity ?? null,
        rows: root?.rows ?? [],
        riskCategories: root?.risk_categories ?? [],
        riskClassifications: root?.risk_classifications ?? [],
    };
}

async function loadDashboard() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/referentials/analyse-risques/${route.params.code}`, {
            params: environmentQueryParams(route),
        });
        const payload = extractPayload(data);
        entity.value = payload.entity;
        rows.value = payload.rows;
        riskCategories.value = payload.riskCategories;
        riskClassifications.value = payload.riskClassifications;
        cartographie.selectedEntityCode = route.params.code;
        cartographie.selectedEntityId = payload.entity?.id ?? null;
        cartographie.selectedDepartment = payload.entity?.name ?? route.params.code;
    } catch {
        error.value = 'Impossible de charger le dashboard de l\'entité.';
    } finally {
        loading.value = false;
    }
}

watch(() => [route.params.code, route.query.environment], loadDashboard);

onMounted(loadDashboard);
</script>

<style scoped>
.departement-dashboard-page {
    max-width: 56rem;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.departement-dashboard-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.departement-dashboard-link {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.departement-dashboard-link:hover {
    color: #0f172a;
}

.departement-dashboard-secondary {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    background: #fff;
}

.departement-dashboard-secondary:hover {
    background: #f8fafc;
}

.departement-dashboard-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.departement-dashboard-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.departement-dashboard-header {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.departement-dashboard-kicker {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
}

.departement-dashboard-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

.departement-dashboard-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.departement-dashboard-metric {
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    border: 1px solid #e2e8f0;
}

.departement-dashboard-metric-gross {
    background: #fff7ed;
    border-color: #fed7aa;
}

.departement-dashboard-metric-residual {
    background: #fefce8;
    border-color: #fef08a;
}

.departement-dashboard-metric-label {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #334155;
}

.departement-dashboard-metric-value {
    margin: 0.35rem 0 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
}

.departement-dashboard-metric-detail {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: #64748b;
}

@media (max-width: 640px) {
    .departement-dashboard-metrics {
        grid-template-columns: 1fr;
    }
}
</style>
