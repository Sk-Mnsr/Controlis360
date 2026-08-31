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

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Évolution mensuelle des recommandations</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Répartition par échéance — 6 derniers mois</p>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm bg-slate-400" /> Total
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm bg-emerald-600" /> Implémentées
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm bg-amber-500" /> En cours
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm bg-red-500" /> En retard
                    </span>
                </div>
            </div>

            <div v-if="!stats.monthly.length" class="py-10 text-center text-sm text-slate-500">
                Pas assez de données d’échéances pour tracer l’évolution.
            </div>

            <div v-else class="mt-5">
                <div class="grid grid-cols-[2.25rem_1fr] gap-2">
                    <div class="relative h-44 text-[10px] font-medium text-slate-400">
                        <span
                            v-for="tick in monthAxisTicks"
                            :key="tick.value"
                            class="absolute right-0 -translate-y-1/2"
                            :style="{ top: `${tick.top}%` }"
                        >
                            {{ tick.value }}
                        </span>
                    </div>

                    <div class="relative h-44 min-w-0">
                        <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                            <div
                                v-for="tick in monthAxisTicks"
                                :key="`grid-${tick.value}`"
                                class="border-t border-dashed border-slate-200"
                            />
                        </div>

                        <div class="relative flex h-full items-end gap-2 px-1 sm:gap-3">
                            <div
                                v-for="month in stats.monthly"
                                :key="month.month"
                                class="group flex h-full min-w-0 flex-1 items-end justify-center"
                            >
                                <div class="flex h-full w-full max-w-[8rem] items-end justify-center gap-1 sm:gap-1.5">
                                    <div
                                        v-for="serie in monthSeries"
                                        :key="serie.key"
                                        class="relative flex h-full w-3.5 flex-col items-center justify-end sm:w-4"
                                        :title="`${serie.label} : ${month[serie.key]}`"
                                    >
                                        <span
                                            v-if="month[serie.key] > 0"
                                            class="mb-1 text-[9px] font-semibold tabular-nums text-slate-600 opacity-0 transition group-hover:opacity-100 sm:opacity-100"
                                        >
                                            {{ month[serie.key] }}
                                        </span>
                                        <div
                                            class="w-full rounded-t-md transition-all duration-300"
                                            :class="serie.barClass"
                                            :style="{ height: `${monthBarHeight(month[serie.key])}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-[2.25rem_1fr] gap-2">
                    <div />
                    <div class="flex gap-2 px-1 sm:gap-3">
                        <div
                            v-for="month in stats.monthly"
                            :key="`label-${month.month}`"
                            class="min-w-0 flex-1 text-center text-[11px] font-semibold text-slate-600"
                        >
                            {{ formatMonth(month.month) }}
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <div class="grid grid-cols-1 gap-4">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Alertes & échéances prochaines</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Référence</th>
                                <th class="min-w-[18rem] px-4 py-2 font-semibold">Recommandation</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Owner</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Échéance</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Jours</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!stats.alerts.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune alerte.</td>
                            </tr>
                            <tr
                                v-for="reco in stats.alerts"
                                :key="reco.id"
                                class="border-t border-slate-100 hover:bg-slate-50"
                            >
                                <td class="whitespace-nowrap px-4 py-2.5 align-top font-medium text-slate-900">
                                    <button type="button" class="hover:text-emerald-700 hover:underline" @click="openReco(reco)">
                                        {{ reco.reference }}
                                    </button>
                                </td>
                                <td class="min-w-[18rem] px-4 py-2.5 align-top text-slate-700 whitespace-normal break-words">
                                    {{ recoLabel(reco) }}
                                </td>
                                <td class="min-w-[8rem] px-4 py-2.5 align-top text-slate-700 whitespace-normal break-words">
                                    {{ reco.responsible_name || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-slate-700">{{ formatDate(reco.due_date) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top font-semibold" :style="remainingDaysTextStyle(reco._remaining)">
                                    {{ reco._remaining ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :style="deadlineToneStyle(reco._deadline.tone)"
                                    >
                                        {{ reco._deadline.label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Top recommandations critiques ouvertes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Référence</th>
                                <th class="min-w-[18rem] px-4 py-2 font-semibold">Recommandation</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Risque</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Échéance</th>
                                <th class="whitespace-nowrap px-4 py-2 font-semibold">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!stats.top_critical.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Aucune reco critique ouverte.</td>
                            </tr>
                            <tr
                                v-for="reco in stats.top_critical"
                                :key="`crit-${reco.id}`"
                                class="border-t border-slate-100 hover:bg-slate-50"
                            >
                                <td class="whitespace-nowrap px-4 py-2.5 align-top font-medium text-slate-900">
                                    <button type="button" class="hover:text-emerald-700 hover:underline" @click="openReco(reco)">
                                        {{ reco.reference }}
                                    </button>
                                </td>
                                <td class="min-w-[18rem] px-4 py-2.5 align-top text-slate-700 whitespace-normal break-words">
                                    {{ recoLabel(reco) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-slate-700">{{ reco.risk_level_fr || reco.risk_level || '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-slate-700">{{ formatDate(reco.due_date) }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :style="deadlineToneStyle(reco._deadline.tone)"
                                    >
                                        {{ reco._deadline.label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import DashboardDonut from './DashboardDonut.vue';
import { useMissionParametrage } from '../../composables/useMissionParametrage';
import { buildAuditDashboardStats, flattenMissionRecommendations } from '../../utils/audit-dashboard';

const props = defineProps({
    missions: { type: Array, default: () => [] },
    showMissionTypeChart: { type: Boolean, default: true },
    fromQuery: { type: String, default: 'dashboard' },
    typeQuery: { type: String, default: '' },
});

const router = useRouter();
const {
    remainingDaysTextStyle,
    deadlineToneStyle,
    resolveDeadlineStatus,
} = useMissionParametrage();

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
const maxMonthTotal = computed(() => Math.max(
    ...stats.value.monthly.flatMap((row) => [row.total, row.implemented, row.in_progress, row.late]),
    1,
));

const monthAxisTicks = computed(() => {
    const max = Math.max(maxMonthTotal.value, 1);
    const steps = Math.min(4, max);
    const values = new Set();
    for (let i = steps; i >= 0; i -= 1) {
        values.add(Math.round((max * i) / steps));
    }
    return [...values]
        .sort((a, b) => b - a)
        .map((value) => ({
            value,
            top: ((max - value) / max) * 100,
        }));
});

const monthSeries = [
    { key: 'total', label: 'Total', barClass: 'bg-slate-400' },
    { key: 'implemented', label: 'Implémentées', barClass: 'bg-emerald-600' },
    { key: 'in_progress', label: 'En cours', barClass: 'bg-amber-500' },
    { key: 'late', label: 'En retard', barClass: 'bg-red-500' },
];

function percentOf(value) {
    if (!stats.value.total) return '0 %';
    return `${Math.round((value / stats.value.total) * 1000) / 10} %`;
}

function entityBarWidth(count) {
    return Math.round((count / maxEntityCount.value) * 100);
}

function monthBarHeight(value) {
    if (!value) return 0;
    return Math.max(8, Math.round((value / maxMonthTotal.value) * 100));
}

function formatMonth(value) {
    const [year, month] = String(value).split('-');
    const labels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
    return `${labels[Number(month) - 1] ?? month} ${String(year).slice(2)}`;
}

function formatDate(value) {
    if (!value) return '—';
    const [year, month, day] = String(value).split('-');
    if (!year || !month || !day) return value;
    return `${day}/${month}/${year}`;
}

function recoLabel(reco) {
    return reco.recommendation_label || reco.name || '—';
}

function openReco(reco) {
    if (!reco?.mission_id) return;
    router.push({
        name: 'audit.missions.show',
        params: { id: reco.mission_id },
        query: {
            from: props.fromQuery,
            ...(props.typeQuery ? { type: props.typeQuery } : {}),
        },
    });
}
</script>
