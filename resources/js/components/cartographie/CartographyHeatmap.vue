<template>
    <section class="cartography-heatmap">
        <header class="cartography-heatmap-header">
            <h2 class="cartography-heatmap-title">{{ title }}</h2>
        </header>

        <div class="cartography-heatmap-body">
            <div class="cartography-heatmap-grid-wrap">
                <table class="cartography-heatmap-table">
                    <thead>
                        <tr>
                            <th class="cartography-heatmap-corner" rowspan="2">P</th>
                            <th class="cartography-heatmap-axis" colspan="6">Gravité (G)</th>
                        </tr>
                        <tr>
                            <th
                                v-for="gravity in gravityLevels"
                                :key="`g-${gravity}`"
                                class="cartography-heatmap-head"
                            >
                                {{ gravity }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in matrixWithEntities" :key="`p-${row[0].probability}`">
                            <th class="cartography-heatmap-probability">{{ row[0].probability }}</th>
                            <td
                                v-for="cell in row"
                                :key="`${cell.gravity}-${cell.probability}`"
                                class="cartography-heatmap-cell"
                                :style="cellStyle(cell)"
                            >
                                <span class="cartography-heatmap-score">{{ cell.score }}</span>
                                <span
                                    v-for="entityName in cell.entities"
                                    :key="entityName"
                                    class="cartography-heatmap-entity"
                                >
                                    {{ entityName }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="cartography-heatmap-caption">
                    Probabilité (P) en lignes — Gravité (G) en colonnes — Score = G × P
                </p>

                <p v-if="!hasEntities && emptyMessage" class="cartography-heatmap-empty">
                    {{ emptyMessage }}
                </p>
            </div>

            <div class="cartography-heatmap-legend" aria-label="Légende des niveaux de risque">
                <p
                    v-for="item in legendItems"
                    :key="item.code"
                    class="cartography-heatmap-legend-item"
                >
                    <span class="cartography-heatmap-legend-swatch" :style="{ backgroundColor: item.color }" />
                    {{ item.name }}
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { buildMatrixWithEntities } from '../../utils/cartographyDashboard';

const props = defineProps({
    title: { type: String, default: 'Carte des risques' },
    matrix: { type: Array, default: () => [] },
    entities: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
    emptyMessage: { type: String, default: '' },
});

const gravityLevels = [1, 2, 3, 4, 5, 6];

const hasEntities = computed(() => props.entities.length > 0);

const matrixWithEntities = computed(() =>
    buildMatrixWithEntities(props.matrix, props.entities),
);

const legendItems = computed(() =>
    [...props.classifications].sort((a, b) => b.sort_order - a.sort_order),
);

function cellStyle(cell) {
    const color = cell.classification?.color ?? '#e5e7eb';
    const isLight = ['#fff176', '#81c784', '#ffb74d'].includes(color);

    return {
        backgroundColor: color,
        color: isLight ? '#111111' : '#ffffff',
    };
}
</script>

<style scoped>
.cartography-heatmap {
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    background: #ffffff;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.cartography-heatmap-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 0.95rem 1.2rem;
}

.cartography-heatmap-title {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #0f172a;
}

.cartography-heatmap-body {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    padding: 1.15rem 1.25rem 1.25rem;
    flex: 1;
}

.cartography-heatmap-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.55rem 1.1rem;
    padding-top: 0.65rem;
    border-top: 1px solid #e2e8f0;
}

.cartography-heatmap-legend-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    color: #334155;
    white-space: nowrap;
}

.cartography-heatmap-legend-swatch {
    width: 0.8rem;
    height: 0.8rem;
    border-radius: 0.15rem;
    flex-shrink: 0;
}

.cartography-heatmap-grid-wrap {
    min-width: 0;
    overflow-x: auto;
}

.cartography-heatmap-table {
    width: 100%;
    min-width: 34rem;
    table-layout: fixed;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.cartography-heatmap-table th,
.cartography-heatmap-table td {
    border: 1px solid #111111;
    text-align: center;
    vertical-align: top;
}

.cartography-heatmap-corner,
.cartography-heatmap-probability {
    width: 2.5rem;
    background: #e5e7eb;
    font-weight: 700;
    font-size: 0.9rem;
}

.cartography-heatmap-axis {
    background: #d1d5db;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.45rem;
}

.cartography-heatmap-head {
    background: #e5e7eb;
    font-weight: 700;
    padding: 0.5rem;
    font-size: 0.9rem;
}

.cartography-heatmap-cell {
    min-height: 5.5rem;
    height: 5.5rem;
    padding: 0.45rem 0.35rem;
    font-weight: 700;
}

.cartography-heatmap-score {
    display: block;
    font-size: 1rem;
    line-height: 1.2;
}

.cartography-heatmap-entity {
    display: block;
    margin-top: 0.3rem;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.25;
    text-transform: uppercase;
    word-break: break-word;
}

.cartography-heatmap-caption {
    margin: 0.8rem 0 0;
    font-size: 0.75rem;
    color: #64748b;
}

.cartography-heatmap-empty {
    margin: 0.85rem 0 0;
    border-radius: 0.5rem;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    padding: 0.7rem 0.9rem;
    font-size: 0.78rem;
    color: #9a3412;
}

@media (max-width: 900px) {
    .cartography-heatmap-cell {
        min-height: 4.5rem;
        height: auto;
    }
}
</style>
