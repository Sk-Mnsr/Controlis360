<template>
    <section class="risk-distribution">
        <header class="risk-distribution-header">
            <h2 class="risk-distribution-title">{{ title }}</h2>
        </header>

        <div class="risk-distribution-body">
            <div class="risk-distribution-chart">
                <svg viewBox="0 0 120 120" class="risk-distribution-svg" aria-hidden="true">
                    <circle
                        cx="60"
                        cy="60"
                        r="42"
                        fill="none"
                        stroke="#f1f5f9"
                        stroke-width="18"
                    />
                    <circle
                        v-for="segment in segments"
                        :key="segment.code"
                        cx="60"
                        cy="60"
                        r="42"
                        fill="none"
                        :stroke="segment.color"
                        stroke-width="18"
                        :stroke-dasharray="segment.dashArray"
                        :stroke-dashoffset="segment.dashOffset"
                        transform="rotate(-90 60 60)"
                    />
                </svg>
                <div class="risk-distribution-center">
                    <span class="risk-distribution-total">{{ total }}</span>
                    <span class="risk-distribution-total-label">entités</span>
                </div>
            </div>

            <ul class="risk-distribution-legend">
                <li v-for="item in items" :key="item.code" class="risk-distribution-legend-item">
                    <span class="risk-distribution-legend-swatch" :style="{ backgroundColor: item.color }" />
                    <span class="risk-distribution-legend-label">{{ item.name }}</span>
                    <span class="risk-distribution-legend-value">{{ item.percent }}%</span>
                </li>
            </ul>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { enrichDistribution } from '../../utils/cartographyDashboard';

const props = defineProps({
    title: { type: String, default: 'Répartition des risques' },
    distribution: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
    totalEntities: { type: Number, default: 0 },
});

const items = computed(() =>
    enrichDistribution(props.distribution, props.classifications),
);

const total = computed(() => props.totalEntities || items.value.reduce((sum, item) => sum + item.count, 0));

const circumference = 2 * Math.PI * 42;

const segments = computed(() => {
    let offset = 0;

    return items.value.map((item) => {
        const length = (item.percent / 100) * circumference;
        const segment = {
            ...item,
            dashArray: `${length} ${circumference - length}`,
            dashOffset: -offset,
        };

        offset += length;

        return segment;
    });
});
</script>

<style scoped>
.risk-distribution {
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    background: #ffffff;
    overflow: hidden;
}

.risk-distribution-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 0.9rem 1.1rem;
}

.risk-distribution-title {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #0f172a;
}

.risk-distribution-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
}

.risk-distribution-chart {
    position: relative;
    width: 9rem;
    height: 9rem;
}

.risk-distribution-svg {
    width: 100%;
    height: 100%;
}

.risk-distribution-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.risk-distribution-total {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.risk-distribution-total-label {
    margin-top: 0.15rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.risk-distribution-legend {
    width: 100%;
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.risk-distribution-legend-item {
    display: grid;
    grid-template-columns: 0.75rem 1fr auto;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #334155;
}

.risk-distribution-legend-swatch {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 999px;
}

.risk-distribution-legend-value {
    font-weight: 700;
    color: #0f172a;
}
</style>
