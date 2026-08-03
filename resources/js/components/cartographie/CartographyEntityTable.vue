<template>
    <section class="cartography-entity-table">
        <header class="cartography-entity-table-header">
            <h2 class="cartography-entity-table-title">{{ title }}</h2>
        </header>

        <div class="cartography-entity-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ entityColumnLabel }}</th>
                        <th v-if="isGroupeScope" class="cartography-entity-table-center">Entités</th>
                        <th class="cartography-entity-table-center">Nombre de risques saisis</th>
                        <th class="cartography-entity-table-center">Fort impact</th>
                        <th>Niveau</th>
                        <th class="cartography-entity-table-center">{{ riskLabel }}</th>
                        <th class="cartography-entity-table-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!entities.length">
                        <td :colspan="colspan" class="cartography-entity-table-empty">
                            {{ emptyMessage }}
                        </td>
                    </tr>
                    <tr v-for="entity in entities" :key="entity.id">
                        <td class="cartography-entity-table-name">{{ entity.name }}</td>
                        <td v-if="isGroupeScope" class="cartography-entity-table-center">
                            {{ entity.entities_count ?? 0 }}
                        </td>
                        <td class="cartography-entity-table-center">{{ entity.rows_count ?? 0 }}</td>
                        <td class="cartography-entity-table-center">{{ entity.high_impact_count ?? 0 }}</td>
                        <td>
                            <span
                                class="cartography-entity-table-level"
                                :style="levelStyle(entity.classification)"
                            >
                                {{ entity.level_label || entity.classification?.name || '—' }}
                            </span>
                        </td>
                        <td
                            class="cartography-entity-table-risk"
                            :style="riskStyle(entity.classification)"
                        >
                            {{ formatValue(entity.risk_score) }}
                        </td>
                        <td class="cartography-entity-table-center">
                            <RouterLink
                                :to="actionRoute(entity)"
                                class="cartography-entity-table-btn"
                            >
                                {{ actionLabel }}
                            </RouterLink>
                        </td>
                    </tr>
                    <tr v-if="averages" class="cartography-entity-table-summary">
                        <td>{{ summaryLabel }}</td>
                        <td v-if="isGroupeScope" class="cartography-entity-table-center">
                            {{ averages.entities_count ?? entities.length }}
                        </td>
                        <td class="cartography-entity-table-center">{{ averages.rows_count ?? 0 }}</td>
                        <td class="cartography-entity-table-center">{{ averages.high_impact_count ?? 0 }}</td>
                        <td>
                            <span
                                class="cartography-entity-table-level"
                                :style="levelStyle(averages.classification)"
                            >
                                {{ averages.level_label || averages.classification?.name || '—' }}
                            </span>
                        </td>
                        <td
                            class="cartography-entity-table-risk"
                            :style="riskStyle(averages.classification)"
                        >
                            {{ formatValue(averages.risk_score) }}
                        </td>
                        <td />
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { formatRiskScore } from '../../utils/riskScore';
import { entityRouteQuery } from '../../utils/entityEnvironment';

const props = defineProps({
    title: { type: String, default: 'Détail des entités' },
    entities: { type: Array, default: () => [] },
    averages: { type: Object, default: null },
    riskLabel: { type: String, default: 'Risque brut' },
    scope: { type: String, default: 'filiale' },
    summaryLabel: { type: String, default: 'FILIALE' },
});

const LIGHT_COLORS = ['#fff176', '#81c784', '#ffb74d', '#c8e6c9', '#dcedc8'];

const isGroupeScope = computed(() => props.scope === 'groupe');

const entityColumnLabel = computed(() => (isGroupeScope.value ? 'Filiale' : 'Entité'));

const actionLabel = computed(() => (isGroupeScope.value ? 'Voir filiale' : 'Cartographie'));

const emptyMessage = computed(() =>
    isGroupeScope.value ? 'Aucune filiale évaluée.' : 'Aucune entité évaluée.',
);

const colspan = computed(() => (isGroupeScope.value ? 7 : 6));

function formatValue(value) {
    return formatRiskScore(value) ?? '—';
}

function actionRoute(entity) {
    if (isGroupeScope.value) {
        return {
            name: 'cartographie.cartographie',
            query: entity.environment?.code ? { environment: entity.environment.code } : {},
        };
    }

    return {
        name: 'cartographie.departement-dashboard',
        params: { code: entity.code },
        query: entityRouteQuery(entity),
    };
}

function levelStyle(classification) {
    const color = classification?.color ?? '#cbd5e1';
    const isLight = LIGHT_COLORS.includes(color) || color === '#cbd5e1';

    return {
        backgroundColor: `${color}22`,
        color: isLight ? '#0f172a' : color,
        borderColor: color,
    };
}

function riskStyle(classification) {
    const color = classification?.color;

    if (!color) {
        return {};
    }

    const isLight = LIGHT_COLORS.includes(color);

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
    vertical-align: middle;
}

th {
    background: #f8fafc;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}

.cartography-entity-table-name {
    font-weight: 600;
    color: #0f172a;
}

.cartography-entity-table-center {
    text-align: center;
}

.cartography-entity-table-level {
    display: inline-block;
    border: 1px solid;
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
}

.cartography-entity-table-risk {
    text-align: center;
    width: 7rem;
    font-weight: 700;
}

.cartography-entity-table-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #16a34a;
    border-radius: 0.45rem;
    padding: 0.3rem 0.65rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #15803d;
    background: #ecfdf5;
    text-decoration: none;
    white-space: nowrap;
}

.cartography-entity-table-btn:hover {
    background: #dcfce7;
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
