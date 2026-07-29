<template>
    <div class="entity-cartography-page">
        <div class="entity-cartography-toolbar">
            <RouterLink
                :to="{
                    name: 'cartographie.departement-analyse',
                    params: { code: route.params.code },
                    query: environmentQueryParams(route),
                }"
                class="entity-cartography-back"
            >
                ← Analyse complète
            </RouterLink>

            <div class="entity-cartography-toolbar-actions">
                <RouterLink
                    :to="{
                        name: 'cartographie.departement-historique',
                        params: { code: route.params.code },
                        query: environmentQueryParams(route),
                    }"
                    class="entity-cartography-btn entity-cartography-btn-ghost"
                >
                    Historique
                </RouterLink>
                <RouterLink
                    :to="{
                        name: 'cartographie.departement-analyse',
                        params: { code: route.params.code },
                        query: environmentQueryParams(route),
                    }"
                    class="entity-cartography-btn entity-cartography-btn-primary"
                >
                    Voir l'analyse
                </RouterLink>
            </div>
        </div>

        <div v-if="loading" class="entity-cartography-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !entity" class="entity-cartography-error">{{ error }}</p>

            <template v-else>
                <header class="entity-cartography-hero">
                    <div>
                        <p class="entity-cartography-kicker">Cartographie entité</p>
                        <h1 class="entity-cartography-title">{{ entity?.name }}</h1>
                        <div class="entity-cartography-meta">
                            <span v-if="entityTypeLabel" class="entity-cartography-chip">{{ entityTypeLabel }}</span>
                            <span v-if="entity?.code" class="entity-cartography-chip">{{ entity.code }}</span>
                            <span v-if="environmentLabel" class="entity-cartography-chip">{{ environmentLabel }}</span>
                        </div>
                    </div>
                </header>

                <section class="entity-cartography-stats" aria-label="Indicateurs">
                    <article class="entity-cartography-stat">
                        <p class="entity-cartography-stat-label">Lignes d'analyse</p>
                        <p class="entity-cartography-stat-value">{{ rows.length }}</p>
                    </article>
                    <article class="entity-cartography-stat">
                        <p class="entity-cartography-stat-label">Fort impact (Rb ≥ 20)</p>
                        <p class="entity-cartography-stat-value">{{ highImpactCount }}</p>
                    </article>
                    <article class="entity-cartography-stat">
                        <p class="entity-cartography-stat-label">Détails renseignés</p>
                        <p class="entity-cartography-stat-value">{{ detailCount }}</p>
                    </article>
                </section>

                <section class="entity-cartography-scores" aria-label="Évaluations">
                    <article class="entity-cartography-score" :style="grossScoreStyle">
                        <div class="entity-cartography-score-top">
                            <p class="entity-cartography-score-label">Risque brut</p>
                            <span v-if="grossLevel" class="entity-cartography-score-level">{{ grossLevel }}</span>
                        </div>
                        <p class="entity-cartography-score-value">
                            {{ formatRiskScore(summaryAverages?.gross.risk) ?? '—' }}
                        </p>
                        <div class="entity-cartography-score-factors">
                            <span>G <strong>{{ formatRiskScore(summaryAverages?.gross.gravity) ?? '—' }}</strong></span>
                            <span>P <strong>{{ formatRiskScore(summaryAverages?.gross.probability) ?? '—' }}</strong></span>
                        </div>
                    </article>

                    <article class="entity-cartography-score entity-cartography-score-residual" :style="residualScoreStyle">
                        <div class="entity-cartography-score-top">
                            <p class="entity-cartography-score-label">Risque résiduel</p>
                            <span v-if="residualLevel" class="entity-cartography-score-level">{{ residualLevel }}</span>
                        </div>
                        <p class="entity-cartography-score-value">
                            {{ formatRiskScore(summaryAverages?.residual.risk) ?? '—' }}
                        </p>
                        <div class="entity-cartography-score-factors">
                            <span>G <strong>{{ formatRiskScore(summaryAverages?.residual.gravity) ?? '—' }}</strong></span>
                            <span>Pr <strong>{{ formatRiskScore(summaryAverages?.residual.probability) ?? '—' }}</strong></span>
                        </div>
                    </article>
                </section>

                <section class="entity-cartography-section">
                    <header class="entity-cartography-section-header">
                        <h2 class="entity-cartography-section-title">Répartition par famille</h2>
                    </header>
                    <RiskCategoryKpiCards
                        :rows="rows"
                        :categories="riskCategories"
                        :classifications="riskClassifications"
                        mode="gross"
                    />
                </section>

                <RiskDetailSummary
                    title="Détail des risques"
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
import {
    classificationForScore,
    computeRiskAverages,
    formatRiskScore,
    scoreStyle,
} from '../../utils/riskScore';
import RiskCategoryKpiCards from '../../components/cartographie/RiskCategoryKpiCards.vue';
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

const entityTypeLabel = computed(() => {
    if (entity.value?.type === 'agency') {
        return 'Agence';
    }

    if (entity.value?.type === 'department') {
        return 'Département';
    }

    return null;
});

const environmentLabel = computed(() => {
    const environment = entity.value?.environment;

    if (!environment) {
        return route.query.environment || null;
    }

    return environment.name || environment.code || null;
});

const highImpactCount = computed(() =>
    rows.value.filter((row) => {
        const gravity = Number(row.gravity);
        const probability = Number(row.probability);

        return gravity > 0 && probability > 0 && gravity * probability >= 20;
    }).length,
);

const detailCount = computed(() => {
    const details = new Set();

    for (const row of rows.value) {
        if (row.correlated_risks) {
            details.add(row.correlated_risks);
        } else if (row.risk_family) {
            details.add(row.risk_family);
        }
    }

    return details.size;
});

const grossClassification = computed(() =>
    classificationForScore(summaryAverages.value?.gross.risk, riskClassifications.value),
);

const residualClassification = computed(() =>
    classificationForScore(summaryAverages.value?.residual.risk, riskClassifications.value),
);

const grossLevel = computed(() => grossClassification.value?.name ?? null);
const residualLevel = computed(() => residualClassification.value?.name ?? null);

const grossScoreStyle = computed(() => {
    const style = scoreStyle(grossClassification.value);

    if (!style.backgroundColor) {
        return { '--score-accent': '#fb923c' };
    }

    return {
        '--score-accent': style.backgroundColor,
        borderColor: `${style.backgroundColor}66`,
        background: `linear-gradient(180deg, ${style.backgroundColor}22 0%, #ffffff 70%)`,
    };
});

const residualScoreStyle = computed(() => {
    const style = scoreStyle(residualClassification.value);

    if (!style.backgroundColor) {
        return { '--score-accent': '#eab308' };
    }

    return {
        '--score-accent': style.backgroundColor,
        borderColor: `${style.backgroundColor}66`,
        background: `linear-gradient(180deg, ${style.backgroundColor}22 0%, #ffffff 70%)`,
    };
});

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
        error.value = 'Impossible de charger la cartographie de l\'entité.';
    } finally {
        loading.value = false;
    }
}

watch(() => [route.params.code, route.query.environment], loadDashboard);

onMounted(loadDashboard);
</script>

<style scoped>
.entity-cartography-page {
    max-width: 72rem;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.35rem;
}

.entity-cartography-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.entity-cartography-back {
    font-size: 0.875rem;
    color: #64748b;
    text-decoration: none;
}

.entity-cartography-back:hover {
    color: #0f172a;
}

.entity-cartography-toolbar-actions {
    display: flex;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.entity-cartography-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    padding: 0.5rem 0.95rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
}

.entity-cartography-btn-ghost {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
}

.entity-cartography-btn-ghost:hover {
    background: #f8fafc;
}

.entity-cartography-btn-primary {
    border: 1px solid #15803d;
    background: #16a34a;
    color: #ffffff;
}

.entity-cartography-btn-primary:hover {
    background: #15803d;
}

.entity-cartography-loading {
    padding: 2.5rem 0;
    text-align: center;
    color: #64748b;
    font-size: 0.9rem;
}

.entity-cartography-error {
    margin: 0;
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.entity-cartography-hero {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background:
        linear-gradient(135deg, #f8fafc 0%, #ffffff 55%),
        radial-gradient(circle at top right, rgba(192, 0, 0, 0.06), transparent 45%);
    padding: 1.25rem 1.4rem;
}

.entity-cartography-kicker {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #c00000;
}

.entity-cartography-title {
    margin: 0.35rem 0 0;
    font-size: 1.85rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
}

.entity-cartography-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.75rem;
}

.entity-cartography-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.2rem 0.65rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #475569;
}

.entity-cartography-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}

.entity-cartography-stat {
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    background: #ffffff;
    padding: 0.95rem 1.05rem;
}

.entity-cartography-stat-label {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}

.entity-cartography-stat-value {
    margin: 0.4rem 0 0;
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.entity-cartography-scores {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.entity-cartography-score {
    position: relative;
    overflow: hidden;
    border: 1px solid #fed7aa;
    border-radius: 1rem;
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 70%);
    padding: 1.15rem 1.25rem;
}

.entity-cartography-score::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--score-accent, #fb923c);
}

.entity-cartography-score-residual {
    border-color: #fde68a;
    background: linear-gradient(180deg, #fefce8 0%, #ffffff 70%);
}

.entity-cartography-score-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.entity-cartography-score-label {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #334155;
}

.entity-cartography-score-level {
    border-radius: 999px;
    background: #0f172a;
    color: #ffffff;
    padding: 0.2rem 0.55rem;
    font-size: 0.68rem;
    font-weight: 700;
}

.entity-cartography-score-value {
    margin: 0.65rem 0 0;
    font-size: 2.35rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #0f172a;
    line-height: 1;
}

.entity-cartography-score-factors {
    display: flex;
    gap: 1rem;
    margin-top: 0.85rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.entity-cartography-score-factors strong {
    color: #0f172a;
    font-weight: 700;
}

.entity-cartography-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.entity-cartography-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.entity-cartography-section-title {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0f172a;
}

@media (max-width: 800px) {
    .entity-cartography-stats,
    .entity-cartography-scores {
        grid-template-columns: 1fr;
    }

    .entity-cartography-title {
        font-size: 1.55rem;
    }
}
</style>
