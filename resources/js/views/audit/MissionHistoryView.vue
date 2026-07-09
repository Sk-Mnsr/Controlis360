<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <RouterLink
                        :to="{ name: 'audit.missions.history' }"
                        class="mb-2 inline-flex items-center text-sm font-medium text-emerald-700 hover:text-emerald-800"
                    >
                        ← Choisir un autre type
                    </RouterLink>
                    <h2 class="text-xl font-semibold">Historique mission</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ listDescription }}</p>
                    <p
                        v-if="selectedTypeLabel"
                        class="mt-2 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800"
                    >
                        {{ selectedTypeLabel }}
                    </p>
                </div>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher une mission..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-500 sm:max-w-xs"
                />
            </div>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">
            {{ error }}
        </div>

        <div v-else-if="!missionsForType.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">{{ emptyMessage }}</p>
        </div>

        <div v-else-if="!filteredMissions.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">Aucune mission ne correspond à votre recherche.</p>
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <p
                v-if="pageEnvironmentLabel"
                class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900"
            >
                {{ pageEnvironmentLabel }}
            </p>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-700">
                        <th class="px-4 py-3 font-semibold">Référence</th>
                        <th class="px-4 py-3 font-semibold">{{ responsibleColumnLabel }}</th>
                        <th v-if="!isResponsible" class="px-4 py-3 font-semibold">Nombre reco</th>
                        <th class="px-4 py-3 font-semibold">Date début</th>
                        <th class="px-4 py-3 font-semibold">Date fin</th>
                        <th class="px-4 py-3 font-semibold">Avancement</th>
                        <th class="px-4 py-3 font-semibold">Jours restants</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="w-14 px-2 py-3 font-semibold text-center" aria-label="Actions" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="mission in filteredMissions"
                        :key="mission.id"
                        class="transition hover:bg-slate-50/80"
                    >
                        <td class="px-4 py-3 font-medium text-slate-900">{{ mission.reference }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ mission.auditor || '—' }}</td>
                        <td v-if="!isResponsible" class="px-4 py-3 text-slate-800">{{ recommendationsLabel(mission) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ formatDate(mission.start_date) }}</td>
                        <td
                            class="px-4 py-3 whitespace-nowrap"
                            :style="remainingDaysTextStyle(missionDaysRemaining(mission))"
                        >
                            {{ formatDate(mission.end_date) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex min-w-[8rem] items-center gap-2">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                    <div
                                        class="h-full rounded-full bg-blue-600 transition-all"
                                        :style="{ width: `${missionProgress(mission)}%` }"
                                    />
                                </div>
                                <span class="w-10 text-right text-xs font-medium text-slate-500">
                                    {{ missionProgress(mission) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="missionDaysRemaining(mission) !== null"
                                class="text-sm"
                                :style="remainingDaysTextStyle(missionDaysRemaining(mission))"
                            >
                                {{ missionDaysRemaining(mission) }}
                            </span>
                            <span v-else class="text-slate-500">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :style="missionStatusStyle(mission.status)">
                                {{ missionStatusLabel(mission.status) || mission.status_fr || mission.status }}
                            </span>
                        </td>
                        <td class="px-2 py-3 text-center">
                            <button
                                v-if="isResponsible"
                                type="button"
                                class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="takingChargeId === mission.id"
                                @click="takeCharge(mission)"
                            >
                                {{ takingChargeId === mission.id ? 'Chargement…' : 'Prendre en charge' }}
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-lg font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                                title="Voir"
                                aria-label="Voir la mission"
                                @click="openDetail(mission)"
                            >
                                ⋯
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useMissionTypes } from '../../composables/useMissionTypes';
import { useMissionParametrage } from '../../composables/useMissionParametrage';
import { isMissionAgent, isMissionResponsible } from '../../config/module-access';
import { useAuthStore } from '../../stores/auth';
import {
    missionImplementationRate,
    missionRemainingDays,
} from '../../utils/mission-progress';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { loadMissionTypes, findType } = useMissionTypes();
const {
    loadMissionParametrage,
    remainingDaysTextStyle,
    missionStatusStyle,
    missionStatusLabel,
} = useMissionParametrage();

const loading = ref(true);
const missions = ref([]);
const error = ref('');
const search = ref('');
const takingChargeId = ref(null);

const isResponsible = computed(() => isMissionResponsible(auth.user));
const isAgent = computed(() => isMissionAgent(auth.user));

const selectedMissionType = computed(() => route.params.missionType ?? '');

const selectedTypeLabel = computed(() => {
    const match = findType(selectedMissionType.value);
    return match?.label ?? selectedMissionType.value;
});

const responsibleColumnLabel = computed(() => {
    const match = findType(selectedMissionType.value);
    const profiles = match?.profiles ?? [];

    if (profiles.includes('controle') && !profiles.includes('audit')) {
        return 'Responsable Contrôle';
    }

    if (auth.user?.profile === 'controle') {
        return 'Responsable Contrôle';
    }

    return 'Responsable Audit';
});

const missionsForType = computed(() => {
    if (!selectedMissionType.value) return missions.value;
    return missions.value.filter((mission) => mission.mission_type === selectedMissionType.value);
});

const filteredMissions = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (!query) return missionsForType.value;

    return missionsForType.value.filter((mission) => missionSearchText(mission).includes(query));
});

const listDescription = computed(() => {
    if (isResponsible.value) {
        return `Historique des missions ${selectedTypeLabel.value || ''} qui vous ont été adressées`.trim();
    }
    if (isAgent.value) {
        return `Historique des missions ${selectedTypeLabel.value || ''} affectées par votre responsable`.trim();
    }
    return `Liste des missions ${selectedTypeLabel.value || ''} enregistrées`.trim();
});

const emptyMessage = computed(() => {
    const suffix = selectedTypeLabel.value ? ` pour ${selectedTypeLabel.value}` : '';
    if (isResponsible.value) return `Aucune mission adressée${suffix}.`;
    if (isAgent.value) return `Aucune mission affectée${suffix}.`;
    return `Aucune mission enregistrée${suffix}.`;
});

const pageEnvironmentLabel = computed(() => {
    const labels = [...new Set(
        filteredMissions.value
            .map((mission) => mission.environment_label ?? environmentLabel(mission))
            .filter((label) => label && label !== '—'),
    )];

    return labels.join(', ');
});

function environmentLabel(mission) {
    const names = (mission.entities ?? [])
        .map((e) => e.environment_name)
        .filter(Boolean);

    return [...new Set(names)].join(', ') || '—';
}

function periodLabel(mission) {
    const start = formatDate(mission.start_date);
    const end = formatDate(mission.end_date);
    return `${start} — ${end}`;
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function recommendationsLabel(mission) {
    const count = mission.recommendations_count ?? mission.reco_stats?.total ?? 0;
    return count === 1 ? '1 reco' : `${count} recos`;
}

function missionProgress(mission) {
    return missionImplementationRate(mission);
}

function missionDaysRemaining(mission) {
    return missionRemainingDays(mission);
}

function missionSearchText(mission) {
    const daysRemaining = missionDaysRemaining(mission);

    return [
        mission.environment_label,
        environmentLabel(mission),
        mission.reference,
        recommendationsLabel(mission),
        mission.auditor,
        mission.mission_type_fr,
        mission.mission_type,
        mission.period,
        periodLabel(mission),
        mission.status_fr,
        mission.status,
        `${missionProgress(mission)}%`,
        daysRemaining !== null ? `${daysRemaining}` : '',
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
}

function extractMissions(data) {
    const root = data?.data ?? data;
    return Array.isArray(root) ? root : [];
}

function openDetail(mission) {
    router.push({
        name: 'audit.missions.show',
        params: { id: mission.id },
        query: {
            from: 'history',
            type: selectedMissionType.value || mission.mission_type,
        },
    });
}

async function takeCharge(mission) {
    takingChargeId.value = mission.id;
    error.value = '';

    try {
        await api.post(`/missions/${mission.id}/take-charge`);
        openDetail(mission);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Impossible de prendre en charge cette mission.';
    } finally {
        takingChargeId.value = null;
    }
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

watch(
    () => route.params.missionType,
    (missionType) => {
        if (!missionType) {
            router.replace({ name: 'audit.missions.history' });
            return;
        }

        if (!findType(missionType)) {
            router.replace({ name: 'audit.missions.history' });
        }
    },
);

onMounted(async () => {
    await loadMissionParametrage();
    await loadMissionTypes();

    const missionType = route.params.missionType;
    if (missionType && !findType(missionType)) {
        router.replace({ name: 'audit.missions.history' });
        return;
    }

    await loadMissions();
});
</script>
