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

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-800">Évolution mensuelle des recommandations</h3>
            <div v-if="!stats.monthly.length" class="py-10 text-center text-sm text-slate-500">
                Pas assez de données d’échéances pour tracer l’évolution.
            </div>
            <div v-else class="mt-4 overflow-x-auto">
                <div class="flex min-w-[28rem] items-end gap-3" style="height: 11rem">
                    <div
                        v-for="month in stats.monthly"
                        :key="month.month"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div class="flex h-36 w-full items-end justify-center gap-1">
                            <div
                                class="w-2 rounded-t bg-slate-400"
                                :style="{ height: `${monthBarHeight(month.total)}%` }"
                                :title="`Total ${month.total}`"
                            />
                            <div
                                class="w-2 rounded-t bg-emerald-600"
                                :style="{ height: `${monthBarHeight(month.implemented)}%` }"
                                :title="`Implémentées ${month.implemented}`"
                            />
                            <div
                                class="w-2 rounded-t bg-amber-500"
                                :style="{ height: `${monthBarHeight(month.in_progress)}%` }"
                                :title="`En cours ${month.in_progress}`"
                            />
                            <div
                                class="w-2 rounded-t bg-red-500"
                                :style="{ height: `${monthBarHeight(month.late)}%` }"
                                :title="`En retard ${month.late}`"
                            />
                        </div>
                        <span class="text-[10px] font-medium text-slate-500">{{ formatMonth(month.month) }}</span>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-slate-400" /> Total</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-emerald-600" /> Implémentées</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-amber-500" /> En cours</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-red-500" /> En retard</span>
                </div>
            </div>
        </article>

        <div class="grid grid-cols-1 gap-4 @[48rem]:grid-cols-2">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Alertes & échéances prochaines</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2 font-semibold">Référence</th>
                                <th class="px-4 py-2 font-semibold">Recommandation</th>
                                <th class="px-4 py-2 font-semibold">Owner</th>
                                <th class="px-4 py-2 font-semibold">Échéance</th>
                                <th class="px-4 py-2 font-semibold">Jours</th>
                                <th class="px-4 py-2 font-semibold">Statut</th>
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
                                <td class="px-4 py-2.5 font-medium text-slate-900">
                                    <button type="button" class="hover:text-emerald-700 hover:underline" @click="openReco(reco)">
                                        {{ reco.reference }}
                                    </button>
                                </td>
                                <td class="max-w-[12rem] truncate px-4 py-2.5 text-slate-700" :title="recoLabel(reco)">
                                    {{ recoLabel(reco) }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">{{ reco.responsible_name || '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-slate-700">{{ formatDate(reco.due_date) }}</td>
                                <td class="px-4 py-2.5 font-semibold" :style="remainingDaysTextStyle(reco._remaining)">
                                    {{ reco._remaining ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5">
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
                                <th class="px-4 py-2 font-semibold">Référence</th>
                                <th class="px-4 py-2 font-semibold">Recommandation</th>
                                <th class="px-4 py-2 font-semibold">Risque</th>
                                <th class="px-4 py-2 font-semibold">Échéance</th>
                                <th class="px-4 py-2 font-semibold">Statut</th>
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
                                <td class="px-4 py-2.5 font-medium text-slate-900">
                                    <button type="button" class="hover:text-emerald-700 hover:underline" @click="openReco(reco)">
                                        {{ reco.reference }}
                                    </button>
                                </td>
                                <td class="max-w-[12rem] truncate px-4 py-2.5 text-slate-700" :title="recoLabel(reco)">
                                    {{ recoLabel(reco) }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">{{ reco.risk_level_fr || reco.risk_level || '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-slate-700">{{ formatDate(reco.due_date) }}</td>
                                <td class="px-4 py-2.5">
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
const maxMonthTotal = computed(() => Math.max(...stats.value.monthly.map((row) => row.total), 1));

function percentOf(value) {
    if (!stats.value.total) return '0 %';
    return `${Math.round((value / stats.value.total) * 1000) / 10} %`;
}

function entityBarWidth(count) {
    return Math.round((count / maxEntityCount.value) * 100);
}

function monthBarHeight(value) {
    return Math.max(4, Math.round((value / maxMonthTotal.value) * 100));
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
