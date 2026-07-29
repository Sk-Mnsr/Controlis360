<template>
    <div class="risk-kpi-strip" role="list">
        <article
            v-for="item in items"
            :key="item.id"
            class="risk-kpi-item"
            role="listitem"
            :style="itemAccent(item)"
        >
            <p class="risk-kpi-item-label">{{ item.label }}</p>
            <p class="risk-kpi-item-score">{{ item.score }}</p>
        </article>

        <article class="risk-kpi-item risk-kpi-item-total" role="listitem">
            <p class="risk-kpi-item-label">Total</p>
            <p class="risk-kpi-item-score">{{ totalScore }}</p>
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

const totalScore = computed(() =>
    items.value.reduce((sum, item) => sum + (Number(item.score) || 0), 0),
);

function itemAccent(item) {
    const color = item.evaluationScore > 0
        ? (item.classification?.color ?? '#94a3b8')
        : '#cbd5e1';

    return {
        '--kpi-accent': color,
    };
}
</script>

<style scoped>
.risk-kpi-strip {
    display: grid;
    grid-template-columns: repeat(8, minmax(0, 1fr));
    gap: 0.55rem;
    padding: 0.55rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.9rem;
    background: #f8fafc;
}

.risk-kpi-item {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.55rem;
    min-height: 4.75rem;
    padding: 0.75rem 0.7rem 0.7rem;
    border-radius: 0.65rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
    overflow: hidden;
}

.risk-kpi-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--kpi-accent, #94a3b8);
}

.risk-kpi-item-label {
    margin: 0;
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.25;
    color: #64748b;
}

.risk-kpi-item-score {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.risk-kpi-item-total {
    background: #0f172a;
    border-color: #0f172a;
}

.risk-kpi-item-total::before {
    background: #c00000;
}

.risk-kpi-item-total .risk-kpi-item-label {
    color: #cbd5e1;
}

.risk-kpi-item-total .risk-kpi-item-score {
    color: #ffffff;
}

@media (max-width: 1200px) {
    .risk-kpi-strip {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .risk-kpi-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
