<template>
    <section class="space-y-4">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Recommandation</h2>
                <p class="mt-1 text-sm text-slate-500">Gérez vos recos :</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-if="canCreate"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-50"
                    @click="importOpen = true"
                >
                    Importer Excel
                </button>
                <RouterLink
                    v-if="canCreate"
                    :to="{ name: 'audit.missions.create' }"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                >
                    + Créer mission
                </RouterLink>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-center text-lg font-bold uppercase tracking-wide text-red-700 underline decoration-red-700 decoration-2 underline-offset-4">
                État de mise en œuvre des recommandations
            </h2>
            <p class="mt-2 text-center text-sm text-slate-500">
                Synthèse par mission du suivi des recommandations
            </p>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">
            {{ error }}
        </div>

        <div v-else-if="!missions.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">Aucune mission enregistrée.</p>
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-red-800 text-left text-white">
                        <th class="border border-white/20 px-4 py-3 font-semibold">Missions</th>
                        <th class="border border-white/20 px-4 py-3 font-semibold text-center">Total recos formulées</th>
                        <th class="border border-white/20 px-4 py-3 font-semibold text-center">Recos totales implémentées</th>
                        <th class="border border-white/20 px-4 py-3 font-semibold text-center">Recos en cours d'implémentation</th>
                        <th class="border border-white/20 px-4 py-3 font-semibold text-center">No start</th>
                        <th class="border border-white/20 px-4 py-3 font-semibold text-center">Taux d'implémentation</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(mission, index) in missions"
                        :key="mission.id"
                        :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-50'"
                    >
                        <td class="border border-white px-4 py-3 font-medium text-slate-900">
                            <button
                                type="button"
                                class="text-left hover:text-emerald-700 hover:underline"
                                @click="openDetail(mission)"
                            >
                                {{ missionLabel(mission) }}
                            </button>
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(mission, 'total') }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(mission, 'implemented') }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(mission, 'in_progress') }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(mission, 'no_start') }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center font-semibold text-slate-900">
                            {{ implementationRate(mission) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="missions.length > 1">
                    <tr class="bg-red-50 font-semibold text-slate-900">
                        <td class="border border-white px-4 py-3">Total</td>
                        <td class="border border-white px-4 py-3 text-center">{{ totals.total }}</td>
                        <td class="border border-white px-4 py-3 text-center">{{ totals.implemented }}</td>
                        <td class="border border-white px-4 py-3 text-center">{{ totals.in_progress }}</td>
                        <td class="border border-white px-4 py-3 text-center">{{ totals.no_start }}</td>
                        <td class="border border-white px-4 py-3 text-center">{{ totals.rate }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <MissionImportModal :open="importOpen" @close="importOpen = false" @imported="onImported" />
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import MissionImportModal from '../../components/audit/MissionImportModal.vue';
import { canCreateMission } from '../../config/module-access';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const loading = ref(true);
const missions = ref([]);
const error = ref('');
const importOpen = ref(false);

const canCreate = computed(() => canCreateMission(auth.user));

const totals = computed(() => {
    const summary = missions.value.reduce(
        (acc, mission) => {
            const stats = mission.reco_stats ?? {};
            acc.total += stats.total ?? 0;
            acc.implemented += stats.implemented ?? 0;
            acc.in_progress += stats.in_progress ?? 0;
            acc.no_start += stats.no_start ?? 0;
            return acc;
        },
        { total: 0, implemented: 0, in_progress: 0, no_start: 0 },
    );

    summary.rate = summary.total > 0 ? Math.round((summary.implemented / summary.total) * 100) : 0;

    return summary;
});

function missionLabel(mission) {
    if (mission.title && mission.title !== mission.reference) {
        return mission.title;
    }

    return mission.reference;
}

function displayStat(mission, key) {
    const value = mission.reco_stats?.[key];
    return value === undefined || value === null || value === 0 ? '' : value;
}

function implementationRate(mission) {
    const rate = mission.reco_stats?.implementation_rate ?? 0;
    return `${rate}%`;
}

function extractMissions(data) {
    const root = data?.data ?? data;
    return Array.isArray(root) ? root : [];
}

async function loadMissions() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/missions');
        missions.value = extractMissions(data);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Impossible de charger les missions.';
    } finally {
        loading.value = false;
    }
}

function openDetail(mission) {
    router.push({
        name: 'audit.missions.show',
        params: { id: mission.id },
        query: { from: 'missions' },
    });
}

function onImported() {
    importOpen.value = false;
    loadMissions();
}

onMounted(loadMissions);
</script>
