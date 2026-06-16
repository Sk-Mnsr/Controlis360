<template>
    <section class="operational-risk-section">
        <table class="operational-risk-table">
            <thead>
                <tr>
                    <th colspan="17" class="operational-risk-title">{{ title }}</th>
                </tr>
                <tr>
                    <th class="operational-risk-head" rowspan="2">N°</th>
                    <th class="operational-risk-head" rowspan="2">Processus</th>
                    <th class="operational-risk-head" rowspan="2">Ratio</th>
                    <th class="operational-risk-head" rowspan="2">Sous processus</th>
                    <th class="operational-risk-head" rowspan="2">Exceptions majeures</th>
                    <th class="operational-risk-head" rowspan="2">Risques corrélés</th>
                    <th class="operational-risk-head operational-risk-head-family" rowspan="2">Famille de risque</th>
                    <th class="operational-risk-head" colspan="3">Risque brut (Rb)</th>
                    <th class="operational-risk-head" colspan="4">Dispositif de prévention et de contrôle</th>
                    <th class="operational-risk-head" colspan="3">Risque résiduel (Rr)</th>
                </tr>
                <tr>
                    <th class="operational-risk-subhead">G</th>
                    <th class="operational-risk-subhead">P</th>
                    <th class="operational-risk-subhead">Rb</th>
                    <th class="operational-risk-subhead">Description</th>
                    <th class="operational-risk-subhead">Existant</th>
                    <th class="operational-risk-subhead">Owner</th>
                    <th class="operational-risk-subhead">Efficacité</th>
                    <th class="operational-risk-subhead">G</th>
                    <th class="operational-risk-subhead">Pr</th>
                    <th class="operational-risk-subhead">Rr</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!rows.length">
                    <td colspan="17" class="operational-risk-empty">Aucune ligne d'analyse pour ce département.</td>
                </tr>
                <tr v-for="row in rows" :key="row.id">
                    <td class="operational-risk-center">{{ row.process_number ?? '—' }}</td>
                    <td>{{ row.process_name || '—' }}</td>
                    <td class="operational-risk-center">{{ formatRatio(row.ratio) }}</td>
                    <td class="operational-risk-strong">{{ row.sub_process_name }}</td>
                    <td>{{ row.major_exceptions || '—' }}</td>
                    <td>{{ row.correlated_risks || '—' }}</td>
                    <td>{{ row.risk_family || '—' }}</td>
                    <td class="operational-risk-center">{{ row.gravity ?? '—' }}</td>
                    <td class="operational-risk-center">{{ row.probability ?? '—' }}</td>
                    <td class="operational-risk-score" :style="scoreStyle(row.gross_classification)">
                        {{ row.gross_risk ?? '—' }}
                    </td>
                    <td>{{ row.control_description || '—' }}</td>
                    <td class="operational-risk-center">{{ formatExists(row.control_exists) }}</td>
                    <td>{{ row.control_owner || '—' }}</td>
                    <td class="operational-risk-center">{{ row.control_effectiveness ?? '—' }}</td>
                    <td class="operational-risk-center">{{ row.residual_gravity ?? '—' }}</td>
                    <td class="operational-risk-center">{{ row.residual_probability ?? '—' }}</td>
                    <td class="operational-risk-score" :style="scoreStyle(row.residual_classification)">
                        {{ row.residual_risk ?? '—' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    rows: { type: Array, default: () => [] },
});

function formatRatio(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `${Number(value)}%`;
}

function formatExists(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return value ? 'OUI' : 'NON';
}

function scoreStyle(classification) {
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
.operational-risk-section {
    overflow-x: auto;
}

.operational-risk-table {
    min-width: 88rem;
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
    line-height: 1.35;
    color: #111111;
}

.operational-risk-table th,
.operational-risk-table td {
    border: 1px solid #111111;
    padding: 0.45rem 0.5rem;
    vertical-align: top;
}

.operational-risk-title {
    background: #c00000;
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
}

.operational-risk-head {
    background: #d1d5db;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    font-size: 0.68rem;
}

.operational-risk-head-family {
    background: #c00000;
    color: #ffffff;
}

.operational-risk-subhead {
    background: #e5e7eb;
    font-weight: 700;
    text-align: center;
    font-size: 0.68rem;
}

.operational-risk-center {
    text-align: center;
    white-space: nowrap;
}

.operational-risk-strong {
    font-weight: 600;
}

.operational-risk-score {
    text-align: center;
    font-weight: 700;
}

.operational-risk-empty {
    text-align: center;
    color: #64748b;
}
</style>
