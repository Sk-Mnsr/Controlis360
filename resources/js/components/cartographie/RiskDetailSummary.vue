<template>
    <section class="risk-detail-summary">
        <header class="risk-detail-summary-header">
            <h2 class="risk-detail-summary-title">{{ title }}</h2>
        </header>

        <div class="risk-detail-summary-table-wrap">
            <table class="risk-detail-summary-table">
                <thead>
                    <tr>
                        <th>Risque</th>
                        <th>Score</th>
                        <th>Niveau</th>
                        <th>Tendance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!items.length">
                        <td colspan="4" class="risk-detail-summary-empty">Aucune donnée disponible.</td>
                    </tr>
                    <tr v-for="item in items" :key="item.id">
                        <td class="risk-detail-summary-risk">{{ item.label }}</td>
                        <td class="risk-detail-summary-score">{{ item.score }}</td>
                        <td>
                            <span class="risk-detail-summary-level">
                                <span
                                    class="risk-detail-summary-dot"
                                    :style="{ backgroundColor: levelColor(item) }"
                                />
                                {{ item.levelLabel }}
                            </span>
                        </td>
                        <td class="risk-detail-summary-trend">{{ item.trend }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { computeRiskCategorySummary } from '../../utils/riskScore';

const props = defineProps({
    title: { type: String, default: 'DÉTAIL DES RISQUES' },
    rows: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
    mode: { type: String, default: 'gross' },
});

const items = computed(() =>
    computeRiskCategorySummary(props.rows, props.categories, props.classifications, props.mode),
);

function levelColor(item) {
    if (!item.evaluationScore || item.evaluationScore <= 0) {
        return '#2e7d32';
    }

    return item.classification?.color ?? '#94a3b8';
}
</script>

<style scoped>
.risk-detail-summary {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #ffffff;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}

.risk-detail-summary-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.25rem;
}

.risk-detail-summary-title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0f172a;
}

.risk-detail-summary-table-wrap {
    overflow-x: auto;
}

.risk-detail-summary-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.risk-detail-summary-table th,
.risk-detail-summary-table td {
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
    vertical-align: middle;
}

.risk-detail-summary-table th {
    background: #f8fafc;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
}

.risk-detail-summary-table tbody tr:last-child td {
    border-bottom: none;
}

.risk-detail-summary-risk {
    font-weight: 500;
    color: #0f172a;
}

.risk-detail-summary-score {
    font-weight: 700;
    color: #0f172a;
}

.risk-detail-summary-level {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #334155;
}

.risk-detail-summary-dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    flex-shrink: 0;
}

.risk-detail-summary-trend {
    font-size: 1rem;
    color: #64748b;
    text-align: center;
}

.risk-detail-summary-empty {
    text-align: center;
    color: #64748b;
}
</style>
