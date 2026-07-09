<template>
    <section class="cartography-entity-table">
        <header class="cartography-entity-table-header">
            <h2 class="cartography-entity-table-title">{{ title }}</h2>
        </header>

        <div class="cartography-entity-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Processus</th>
                        <th>Gravité</th>
                        <th>{{ probabilityLabel }}</th>
                        <th>{{ riskLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!entities.length">
                        <td colspan="4" class="cartography-entity-table-empty">Aucune entité évaluée.</td>
                    </tr>
                    <tr v-for="entity in entities" :key="entity.id">
                        <td>{{ entity.name }}</td>
                        <td class="cartography-entity-table-num">{{ formatValue(entity.gravity) }}</td>
                        <td class="cartography-entity-table-num">{{ formatValue(entity.probability) }}</td>
                        <td
                            class="cartography-entity-table-risk"
                            :style="riskStyle(entity.classification)"
                        >
                            {{ formatValue(entity.risk_score) }}
                        </td>
                    </tr>
                    <tr v-if="averages" class="cartography-entity-table-summary">
                        <td>FILIALE</td>
                        <td class="cartography-entity-table-num">{{ formatValue(averages.gravity) }}</td>
                        <td class="cartography-entity-table-num">{{ formatValue(averages.probability) }}</td>
                        <td
                            class="cartography-entity-table-risk"
                            :style="riskStyle(averages.classification)"
                        >
                            {{ formatValue(averages.risk_score) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { formatRiskScore } from '../../utils/riskScore';

defineProps({
    title: { type: String, default: 'Détail des entités' },
    entities: { type: Array, default: () => [] },
    averages: { type: Object, default: null },
    probabilityLabel: { type: String, default: 'Probabilité' },
    riskLabel: { type: String, default: 'Risque brut' },
});

function formatValue(value) {
    return formatRiskScore(value) ?? '—';
}

function riskStyle(classification) {
    const color = classification?.color;

    if (!color) {
        return {};
    }

    const isLight = ['#fff176', '#81c784', '#ffb74d'].includes(color);

    return {
        backgroundColor: color,
        color: isLight ? '#111111' : '#ffffff',
        fontWeight: '700',
    };
}
</script>

<style scoped>
.cartography-entity-table {
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    background: #ffffff;
    overflow: hidden;
}

.cartography-entity-table-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 0.9rem 1.1rem;
}

.cartography-entity-table-title {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #0f172a;
}

.cartography-entity-table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

th,
td {
    padding: 0.7rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

th {
    background: #f8fafc;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}

.cartography-entity-table-num,
.cartography-entity-table-risk {
    text-align: center;
    width: 6rem;
}

.cartography-entity-table-summary {
    background: #fff7ed;
    font-weight: 700;
}

.cartography-entity-table-summary td:first-child {
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.cartography-entity-table-empty {
    text-align: center;
    color: #64748b;
}
</style>
