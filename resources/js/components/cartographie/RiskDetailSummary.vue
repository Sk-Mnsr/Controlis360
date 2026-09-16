<template>
    <section class="risk-detail-summary">
        <header class="risk-detail-summary-header">
            <h2 class="risk-detail-summary-title">{{ title }}</h2>
        </header>

        <div class="risk-detail-summary-table-wrap">
            <table class="risk-detail-summary-table">
                <thead>
                    <tr>
                        <th>Niveau</th>
                        <th class="risk-detail-summary-center">Occurrences</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!items.length">
                        <td colspan="2" class="risk-detail-summary-empty">Aucun détail renseigné.</td>
                    </tr>
                    <tr v-for="item in items" :key="item.code">
                        <td class="risk-detail-summary-risk">
                            <span
                                class="risk-detail-summary-bar"
                                :style="{ backgroundColor: item.color }"
                                :title="item.name"
                            />
                            {{ item.name }}
                        </td>
                        <td class="risk-detail-summary-score">{{ item.occurrences }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { classificationForCell } from '../../utils/riskScore';

const props = defineProps({
    title: { type: String, default: 'DÉTAIL DES RISQUES' },
    rows: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
});

const NEUTRAL_COLOR = '#cbd5e1';

const items = computed(() => {
    const counts = new Map();

    for (const row of props.rows) {
        const gravity = Number(row.gravity);
        const probability = Number(row.probability);

        if (!gravity || !probability) {
            continue;
        }

        const classification = classificationForCell(gravity, probability, props.classifications);
        const code = classification?.code ?? 'unknown';

        counts.set(code, (counts.get(code) ?? 0) + 1);
    }

    const legend = [...props.classifications]
        .sort((a, b) => b.sort_order - a.sort_order)
        .map((level) => ({
            code: level.code,
            name: level.name,
            color: level.color ?? NEUTRAL_COLOR,
            occurrences: counts.get(level.code) ?? 0,
        }));

    if (legend.length) {
        return legend;
    }

    // Fallback si aucune classification n'est chargée
    return [...counts.entries()].map(([code, occurrences]) => ({
        code,
        name: code,
        color: NEUTRAL_COLOR,
        occurrences,
    }));
});
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
    table-layout: fixed;
}

.risk-detail-summary-table th,
.risk-detail-summary-table td {
    padding: 0.8rem 1rem;
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

.risk-detail-summary-table th:nth-child(1),
.risk-detail-summary-table td:nth-child(1) {
    width: 72%;
}

.risk-detail-summary-table th:nth-child(2),
.risk-detail-summary-table td:nth-child(2) {
    width: 28%;
}

.risk-detail-summary-center {
    text-align: center;
}

.risk-detail-summary-table tbody tr:last-child td {
    border-bottom: none;
}

.risk-detail-summary-risk {
    font-weight: 500;
    color: #0f172a;
}

.risk-detail-summary-bar {
    display: inline-block;
    width: 0.3rem;
    height: 1.1rem;
    border-radius: 999px;
    margin-right: 0.6rem;
    vertical-align: middle;
}

.risk-detail-summary-score {
    font-weight: 700;
    color: #0f172a;
    text-align: center;
}

.risk-detail-summary-empty {
    text-align: center;
    color: #64748b;
}
</style>
