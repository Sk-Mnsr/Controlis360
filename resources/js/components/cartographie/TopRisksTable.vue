<template>
    <section class="top-risks-section">
        <div class="top-risks-banner">
            <h2 class="top-risks-banner-title">{{ title }}</h2>
        </div>

        <div class="top-risks-scroll">
            <table class="top-risks-table">
                <thead>
                    <tr>
                        <th class="top-risks-head top-risks-col-process">Processus</th>
                        <th class="top-risks-head top-risks-col-subprocess">Sous processus</th>
                        <th class="top-risks-head top-risks-col-risks">Risques identifiés</th>
                        <th class="top-risks-head top-risks-head-family top-risks-col-family">Famille de risque</th>
                        <th class="top-risks-head top-risks-col-description">Description</th>
                        <th class="top-risks-head top-risks-col-exists">Existant</th>
                        <th class="top-risks-head top-risks-col-rb">
                            Risque brut (Rb)
                            <span class="top-risks-formula">G × P</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="7" class="top-risks-empty">Aucun risque à fort impact (Rb ≥ 20) enregistré.</td>
                    </tr>
                    <template v-for="group in groupedRows" :key="group.process_name">
                        <tr
                            v-for="(row, index) in group.rows"
                            :key="row.id"
                            class="top-risks-row"
                        >
                            <td
                                v-if="index === 0"
                                class="top-risks-process"
                                :rowspan="group.rows.length"
                            >
                                <div class="top-risks-process-inner">
                                    {{ group.process_name }}
                                </div>
                            </td>
                            <td class="top-risks-subprocess">{{ row.sub_process_name }}</td>
                            <td class="top-risks-text">{{ row.major_exceptions || '—' }}</td>
                            <td class="top-risks-family">
                                <div class="top-risks-vcenter">
                                    <span class="top-risks-family-chip">{{ row.risk_family || '—' }}</span>
                                </div>
                            </td>
                            <td class="top-risks-text">{{ row.control_description || '—' }}</td>
                            <td class="top-risks-exists">
                                <div class="top-risks-vcenter">
                                    <span
                                        class="top-risks-exists-badge"
                                        :class="existsClass(row.control_exists)"
                                    >
                                        {{ formatExists(row.control_exists) }}
                                    </span>
                                </div>
                            </td>
                            <td class="top-risks-rb" :style="rbStyle(row)">
                                <div class="top-risks-vcenter">
                                    {{ row.gross_risk ?? '—' }}
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
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

function formatExists(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return value ? 'OUI' : 'NON';
}

function existsClass(value) {
    if (value === null || value === undefined) {
        return 'is-empty';
    }

    return value ? 'is-yes' : 'is-no';
}

function rbStyle(row) {
    const color = row.classification?.color ?? row.gross_classification?.color;

    if (!color) {
        return {};
    }

    const isLight = ['#fff176', '#81c784', '#ffb74d'].includes(color);

    return {
        backgroundColor: color,
        color: isLight ? '#111111' : '#ffffff',
    };
}
</script>

<style scoped>
.top-risks-section {
    width: 100%;
    display: flex;
    flex-direction: column;
    border-radius: 0.85rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    overflow: hidden;
}

.top-risks-banner {
    background: linear-gradient(90deg, #c00000 0%, #9f0000 100%);
    padding: 0.85rem 1.15rem;
}

.top-risks-banner-title {
    margin: 0;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.top-risks-scroll {
    width: 100%;
    overflow-x: auto;
}

.top-risks-table {
    width: 100%;
    min-width: 72rem;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #0f172a;
}

.top-risks-table th,
.top-risks-table td {
    padding: 0.85rem 0.9rem;
    vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
    word-break: break-word;
    overflow-wrap: anywhere;
}

.top-risks-head {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
    font-weight: 700;
    text-align: left;
    text-transform: uppercase;
    font-size: 0.68rem;
    letter-spacing: 0.04em;
    color: #475569;
    border-bottom: 1px solid #cbd5e1;
    white-space: nowrap;
}

.top-risks-head-family {
    background: #c00000;
    color: #ffffff;
}

.top-risks-formula {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
    opacity: 0.8;
}

.top-risks-col-process { width: 9%; }
.top-risks-col-subprocess { width: 16%; }
.top-risks-col-risks { width: 22%; }
.top-risks-col-family { width: 12%; }
.top-risks-col-description { width: 27%; }
.top-risks-col-exists { width: 7%; }
.top-risks-col-rb { width: 7%; }

.top-risks-row:nth-child(even) td:not(.top-risks-process):not(.top-risks-rb) {
    background: #fcfcfd;
}

.top-risks-row:hover td:not(.top-risks-rb) {
    background: #fff7f7;
}

.top-risks-process {
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #7f1d1d;
    background: #fef2f2;
    border-right: 1px solid #fecaca;
}

.top-risks-table td.top-risks-process {
    vertical-align: middle;
    text-align: center;
}

.top-risks-process-inner,
.top-risks-vcenter {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 100%;
}

.top-risks-subprocess {
    font-weight: 600;
    color: #1e293b;
}

.top-risks-text {
    color: #334155;
    white-space: pre-line;
}

.top-risks-family {
    text-align: center;
}

.top-risks-table td.top-risks-family,
.top-risks-table td.top-risks-exists,
.top-risks-table td.top-risks-rb {
    vertical-align: middle;
}

.top-risks-family-chip {
    display: inline-block;
    max-width: 100%;
    padding: 0.3rem 0.55rem;
    border-radius: 0.45rem;
    background: #f1f5f9;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.3;
}

.top-risks-exists {
    text-align: center;
}

.top-risks-exists-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 3.1rem;
    padding: 0.25rem 0.45rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.top-risks-exists-badge.is-yes {
    background: #ecfdf5;
    color: #047857;
}

.top-risks-exists-badge.is-no {
    background: #fef2f2;
    color: #b91c1c;
}

.top-risks-exists-badge.is-empty {
    background: #f1f5f9;
    color: #64748b;
}

.top-risks-rb {
    text-align: center;
    font-size: 1rem;
    font-weight: 800;
}

.top-risks-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #64748b;
}
</style>
