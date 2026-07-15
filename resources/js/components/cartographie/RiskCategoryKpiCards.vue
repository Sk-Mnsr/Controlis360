<template>
    <div class="risk-kpi-cards">
        <article
            v-for="item in items"
            :key="item.id"
            class="risk-kpi-card"
            :style="cardStyle(item)"
        >
            <p class="risk-kpi-card-label">{{ item.label }}</p>
            <p class="risk-kpi-card-score">{{ item.score }}</p>
            <p class="risk-kpi-card-level">{{ item.levelLabel }}</p>
        </article>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { computeRiskCategorySummary } from '../../utils/riskScore';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
    mode: { type: String, default: 'gross' },
});

const items = computed(() =>
    computeRiskCategorySummary(props.rows, props.categories, props.classifications, props.mode),
);

function cardStyle(item) {
    const color = item.evaluationScore > 0
        ? (item.classification?.color ?? '#e2e8f0')
        : '#dcfce7';

    const isLight = ['#fff176', '#81c784', '#ffb74d', '#dcfce7'].includes(color);

    return {
        borderColor: color,
        background: `linear-gradient(180deg, ${color}22 0%, #ffffff 100%)`,
        '--kpi-accent': color,
        '--kpi-text': isLight ? '#0f172a' : '#ffffff',
    };
}
</script>

<style scoped>
.risk-kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
    gap: 0.75rem;
}

.risk-kpi-card {
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    padding: 0.9rem 1rem;
    background: #ffffff;
    min-height: 6.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.risk-kpi-card-label {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1.3;
    color: #334155;
}

.risk-kpi-card-score {
    margin: 0.35rem 0 0;
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.risk-kpi-card-level {
    margin: 0.35rem 0 0;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--kpi-accent, #64748b);
}
</style>
