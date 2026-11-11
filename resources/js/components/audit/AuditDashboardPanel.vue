<template>
    <div class="@container space-y-5">
        <div class="grid grid-cols-1 gap-3 @[28rem]:grid-cols-2 @[42rem]:grid-cols-3 @[56rem]:grid-cols-6">
            <article
                v-for="card in kpiCards"
                :key="card.label"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold"
                        :class="card.badgeClass"
                    >
                        {{ card.icon }}
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ card.value }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
            </article>
        </div>

        <div class="grid grid-cols-1 gap-4 @[36rem]:grid-cols-2 @[56rem]:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800">Répartition par niveau de risque</h3>
                <DashboardDonut
                    class="mt-4"
                    :items="riskDonutItems"
                    :total="stats.total"
                    total-label="recos"
                />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800">Répartition par statut</h3>
                <DashboardDonut
                    class="mt-4"
                    :items="statusDonutItems"
                    :total="stats.total"
                    total-label="recos"
                />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800">Respect des délais</h3>
                <div class="mt-6 flex flex-col items-center">
                    <div
                        class="relative flex h-36 w-36 items-end justify-center overflow-hidden rounded-full"
                        :style="gaugeStyle"
                    >
                        <div class="absolute bottom-0 h-[72px] w-[144px] rounded-t-full bg-white" />
                        <div class="relative z-10 mb-2 text-center">
                            <p class="text-3xl font-bold text-slate-900">{{ stats.on_time_rate }}%</p>
                            <p class="text-xs text-slate-500">dans les délais</p>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs text-slate-500">
                        {{ stats.late }} recommandation{{ stats.late > 1 ? 's' : '' }} en retard
                    </p>
                </div>
            </article>
        </div>

        <div class="grid grid-cols-1 gap-4" :class="showMissionTypeChart ? '@[48rem]:grid-cols-2' : ''">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800">Recommandations par direction / entité</h3>
                <div v-if="!stats.by_entity.length" class="py-10 text-center text-sm text-slate-500">
                    Aucune donnée
                </div>
                <ul v-else class="mt-4 space-y-3">
                    <li v-for="row in stats.by_entity" :key="row.id" class="grid grid-cols-[minmax(4rem,7rem)_1fr_2.5rem] items-center gap-2">
                        <span class="truncate text-xs font-medium text-slate-600" :title="row.name">{{ row.name }}</span>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-emerald-600"
                                :style="{ width: `${entityBarWidth(row.count)}%` }"
                            />
                        </div>
                        <span class="text-right text-xs font-semibold text-slate-800">{{ row.count }}</span>
                    </li>
                </ul>
            </article>

            <article
                v-if="showMissionTypeChart"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <h3 class="text-sm font-semibold text-slate-800">Recommandations par type de mission</h3>
                <DashboardDonut
                    class="mt-4"
                    :items="missionTypeDonutItems"
                    :total="stats.total"
                    total-label="recos"
                />
            </article>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import DashboardDonut from './DashboardDonut.vue';
import { useMissionParametrage } from '../../composables/useMissionParametrage';
import { buildAuditDashboardStats, flattenMissionRecommendations } from '../../utils/audit-dashboard';

const props = defineProps({
    missions: { type: Array, default: () => [] },
    showMissionTypeChart: { type: Boolean, default: true },
    fromQuery: { type: String, default: 'dashboard' },
    typeQuery: { type: String, default: '' },
});

const { resolveDeadlineStatus } = useMissionParametrage();

const recommendations = computed(() => flattenMissionRecommendations(props.missions));
const stats = computed(() => buildAuditDashboardStats(recommendations.value, resolveDeadlineStatus));

const kpiCards = computed(() => [
    {
        label: 'Total recommandations',
        value: stats.value.total,
        hint: '100 % du périmètre',
        icon: 'Σ',
        badgeClass: 'bg-slate-100 text-slate-700',
    },
    {
        label: 'Clôturées',
        value: stats.value.implemented,
        hint: percentOf(stats.value.implemented),
        icon: '✓',
        badgeClass: 'bg-emerald-100 text-emerald-800',
    },
    {
        label: 'En cours',
        value: stats.value.in_progress,
        hint: percentOf(stats.value.in_progress),
        icon: '…',
        badgeClass: 'bg-amber-100 text-amber-800',
    },
    {
        label: 'En retard',
        value: stats.value.late,
        hint: percentOf(stats.value.late),
        icon: '!',
        badgeClass: 'bg-red-100 text-red-700',
    },
    {
        label: 'Taux de mise en œuvre',
        value: `${stats.value.implementation_rate}%`,
        hint: 'Recos implémentées / total',
        icon: '%',
        badgeClass: 'bg-violet-100 text-violet-800',
    },
    {
        label: 'Critiques ouvertes',
        value: stats.value.critical_open,
        hint: `${stats.value.critical_late} en retard`,
        icon: '⚠',
        badgeClass: 'bg-yellow-100 text-yellow-800',
    },
]);

const RISK_COLORS = ['#dc2626', '#ea580c', '#ca8a04', '#64748b', '#0f766e', '#1d4ed8'];
const STATUS_COLORS = {
    implemented: '#047857',
    in_progress: '#d97706',
    no_start: '#64748b',
};
const TYPE_COLORS = ['#047857', '#0f766e', '#1d4ed8', '#7c3aed', '#be123c', '#ea580c'];

const riskDonutItems = computed(() => stats.value.by_risk.map((item, index) => ({
    ...item,
    color: RISK_COLORS[index % RISK_COLORS.length],
})));

const statusDonutItems = computed(() => stats.value.by_status.map((item) => ({
    ...item,
    color: STATUS_COLORS[item.code] ?? '#64748b',
})));

const missionTypeDonutItems = computed(() => stats.value.by_mission_type.map((item, index) => ({
    ...item,
    color: TYPE_COLORS[index % TYPE_COLORS.length],
})));

const gaugeStyle = computed(() => {
    const rate = Math.min(100, Math.max(0, stats.value.on_time_rate));
    return {
        background: `conic-gradient(#047857 0% ${rate}%, #e2e8f0 ${rate}% 100%)`,
    };
});

const maxEntityCount = computed(() => Math.max(...stats.value.by_entity.map((row) => row.count), 1));
function percentOf(value) {
    if (!stats.value.total) return '0 %';
    return `${Math.round((value / stats.value.total) * 1000) / 10} %`;
}

function entityBarWidth(count) {
    return Math.round((count / maxEntityCount.value) * 100);
}

</script>
