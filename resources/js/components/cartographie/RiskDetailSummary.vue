<template>
    <section class="risk-detail-summary">
        <header class="risk-detail-summary-header">
            <h2 class="risk-detail-summary-title">{{ title }}</h2>
        </header>

        <div class="risk-detail-summary-table-wrap">
            <table class="risk-detail-summary-table">
                <thead>
                    <tr>
                        <th>Détails par famille</th>
                        <th>Famille</th>
                        <th class="risk-detail-summary-center">Occurrences</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!items.length">
                        <td colspan="3" class="risk-detail-summary-empty">Aucun détail renseigné.</td>
                    </tr>
                    <tr v-for="item in items" :key="item.detail">
                        <td class="risk-detail-summary-risk">
                            <span
                                class="risk-detail-summary-bar"
                                :style="{ backgroundColor: item.color }"
                                :title="item.levelLabel"
                            />
                            {{ item.detail }}
                        </td>
                        <td class="risk-detail-summary-family">{{ item.category }}</td>
                        <td class="risk-detail-summary-score">{{ item.occurrences }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { classificationForScore } from '../../utils/riskScore';

const props = defineProps({
    title: { type: String, default: 'DÉTAIL DES RISQUES' },
    rows: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
});

const NEUTRAL_COLOR = '#cbd5e1';

const detailCategoryMap = computed(() => {
    const map = new Map();

    for (const category of props.categories) {
        for (const detail of category.families ?? []) {
            map.set(detail.name, category.name);
        }
    }

    return map;
});

function grossRisk(row) {
    const gravity = Number(row.gravity);
    const probability = Number(row.probability);

    if (!gravity || !probability) {
        return 0;
    }

    return gravity * probability;
}

const items = computed(() => {
    const grouped = new Map();

    for (const row of props.rows) {
        // Les nouvelles lignes stockent le détail dans correlated_risks.
        // Pour les anciennes lignes, risk_family peut encore contenir ce détail.
        const detail = detailCategoryMap.value.has(row.correlated_risks)
            ? row.correlated_risks
            : (detailCategoryMap.value.has(row.risk_family) ? row.risk_family : null);

        if (!detail) {
            continue;
        }

        const current = grouped.get(detail);
        const risk = grossRisk(row);

        grouped.set(detail, {
            detail,
            category: detailCategoryMap.value.get(detail) ?? row.risk_family ?? '—',
            occurrences: (current?.occurrences ?? 0) + 1,
            maxRisk: Math.max(current?.maxRisk ?? 0, risk),
        });
    }

    return [...grouped.values()]
        .map((item) => {
            const classification = classificationForScore(item.maxRisk, props.classifications);

            return {
                ...item,
                color: classification?.color ?? NEUTRAL_COLOR,
                levelLabel: classification?.name ?? 'Non significatif',
            };
        })
        .sort((a, b) =>
            b.maxRisk - a.maxRisk
            || b.occurrences - a.occurrences
            || a.detail.localeCompare(b.detail, 'fr'),
        );
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
    width: 46%;
}

.risk-detail-summary-table th:nth-child(2),
.risk-detail-summary-table td:nth-child(2) {
    width: 38%;
}

.risk-detail-summary-table th:nth-child(3),
.risk-detail-summary-table td:nth-child(3) {
    width: 16%;
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

.risk-detail-summary-family {
    font-size: 0.78rem;
    color: #64748b;
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
