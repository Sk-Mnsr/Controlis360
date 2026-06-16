<template>
    <section class="risk-matrix-section">
        <div class="risk-matrix-layout">
            <div class="risk-matrix-annotations">
                <div
                    v-for="item in rowAnnotations"
                    :key="item.label"
                    class="risk-matrix-annotation"
                    :style="{ color: item.color }"
                >
                    <span class="risk-matrix-annotation-arrow">→</span>
                    <span class="risk-matrix-annotation-label">{{ item.label }}</span>
                </div>
            </div>

            <div class="risk-matrix-grid-wrap">
                <table class="risk-matrix-table">
                    <thead>
                        <tr>
                            <th class="risk-matrix-corner" rowspan="2">P</th>
                            <th class="risk-matrix-axis" colspan="6">Gravité (G)</th>
                        </tr>
                        <tr>
                            <th
                                v-for="gravity in gravityLevels"
                                :key="`g-${gravity}`"
                                class="risk-matrix-head"
                            >
                                {{ gravity }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in matrix" :key="`p-${row[0].probability}`">
                            <th class="risk-matrix-probability">{{ row[0].probability }}</th>
                            <td
                                v-for="cell in row"
                                :key="`${cell.gravity}-${cell.probability}`"
                                class="risk-matrix-cell"
                                :style="cellStyle(cell)"
                            >
                                {{ cell.score }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="risk-matrix-legend">
                    Probabilité (P) en lignes — Gravité (G) en colonnes — Score = G × P
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    matrix: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
});

const gravityLevels = [1, 2, 3, 4, 5, 6];

const rowAnnotations = computed(() => {
    const ordered = [...props.classifications].sort((a, b) => b.sort_order - a.sort_order);

    return ordered.map((item) => ({
        label: item.name,
        color: item.color ?? '#111111',
    }));
});

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
.risk-matrix-section {
    overflow-x: auto;
}

.risk-matrix-layout {
    display: flex;
    gap: 1rem;
    align-items: stretch;
}

.risk-matrix-annotations {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 8.5rem;
    padding: 3.2rem 0 1.5rem;
}

.risk-matrix-annotation {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.2;
}

.risk-matrix-annotation-arrow {
    font-size: 0.85rem;
}

.risk-matrix-grid-wrap {
    flex: 1;
    min-width: 0;
}

.risk-matrix-table {
    width: 100%;
    max-width: 36rem;
    border-collapse: collapse;
    font-size: 0.875rem;
    color: #111111;
}

.risk-matrix-table th,
.risk-matrix-table td {
    border: 1px solid #111111;
    text-align: center;
    vertical-align: middle;
}

.risk-matrix-corner,
.risk-matrix-probability {
    width: 2.5rem;
    background: #e5e7eb;
    font-weight: 700;
}

.risk-matrix-axis {
    background: #d1d5db;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.risk-matrix-head {
    width: 3.5rem;
    background: #e5e7eb;
    font-weight: 700;
    padding: 0.45rem;
}

.risk-matrix-cell {
    height: 2.75rem;
    font-weight: 700;
    font-size: 0.95rem;
}

.risk-matrix-legend {
    margin-top: 0.75rem;
    font-size: 0.75rem;
    color: #64748b;
}
</style>
