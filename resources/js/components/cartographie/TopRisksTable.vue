<template>
    <section class="top-risks-section">
        <table class="top-risks-table">
            <thead>
                <tr>
                    <th colspan="7" class="top-risks-title">{{ title }}</th>
                </tr>
                <tr>
                    <th class="top-risks-head" rowspan="2">Processus</th>
                    <th class="top-risks-head" rowspan="2">Sous processus</th>
                    <th class="top-risks-head" rowspan="2">Exceptions majeures constatées</th>
                    <th class="top-risks-head top-risks-head-family" rowspan="2">Famille de risque</th>
                    <th class="top-risks-head" colspan="2">Évaluation</th>
                    <th class="top-risks-head" rowspan="2">
                        Risque brut (Rb)<br />
                        <span class="top-risks-formula">est égal à G × P</span>
                    </th>
                </tr>
                <tr>
                    <th class="top-risks-subhead">Gravité (G)</th>
                    <th class="top-risks-subhead">Probabilité (P)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!rows.length">
                    <td colspan="7" class="top-risks-empty">Aucun risque à fort impact (Rb ≥ 10) enregistré.</td>
                </tr>
                <template v-for="group in groupedRows" :key="group.process_name">
                    <tr v-for="(row, index) in group.rows" :key="row.id">
                        <td
                            v-if="index === 0"
                            class="top-risks-process"
                            :rowspan="group.rows.length"
                        >
                            {{ group.process_name }}
                        </td>
                        <td class="top-risks-subprocess">{{ row.sub_process_name }}</td>
                        <td>{{ row.major_exceptions || '—' }}</td>
                        <td class="top-risks-family">{{ row.risk_family || '—' }}</td>
                        <td class="top-risks-score">{{ row.gravity ?? '—' }}</td>
                        <td class="top-risks-score">{{ row.probability ?? '—' }}</td>
                        <td class="top-risks-rb" :style="rbStyle(row)">
                            {{ row.gross_risk ?? '—' }}
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { groupRowsByProcess } from '../../utils/operationalRiskGroups';

const props = defineProps({
    title: { type: String, default: 'RISQUES OPERATIONNELS A FORT IMPACT BUSINESS' },
    rows: { type: Array, default: () => [] },
});

const groupedRows = computed(() => groupRowsByProcess(props.rows));

function rbStyle(row) {
    const color = row.classification?.color ?? row.gross_classification?.color;

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
.top-risks-section {
    overflow-x: auto;
}

.top-risks-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    line-height: 1.4;
    color: #111111;
}

.top-risks-table th,
.top-risks-table td {
    border: 1px solid #111111;
    padding: 0.55rem 0.65rem;
    vertical-align: top;
}

.top-risks-title {
    background: #c00000;
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.top-risks-head {
    background: #d1d5db;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    font-size: 0.72rem;
}

.top-risks-head-family {
    background: #c00000;
    color: #ffffff;
}

.top-risks-subhead {
    background: #e5e7eb;
    font-weight: 700;
    text-align: center;
    font-size: 0.72rem;
}

.top-risks-formula {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: none;
}

.top-risks-process {
    font-weight: 700;
    text-transform: uppercase;
    vertical-align: middle;
}

.top-risks-subprocess {
    font-weight: 600;
}

.top-risks-family,
.top-risks-score,
.top-risks-rb {
    text-align: center;
}

.top-risks-empty {
    text-align: center;
    color: #64748b;
}
</style>
