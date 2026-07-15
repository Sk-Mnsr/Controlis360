<template>
    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Missions</h2>
            <p class="mt-1 text-sm text-slate-500">
                Sélectionnez un type de mission pour consulter son historique.
            </p>
        </div>

        <div v-if="loading || typesLoading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">
            {{ error }}
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <button
                v-for="type in visibleTypes"
                :key="type.value"
                type="button"
                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md"
                @click="openHistory(type.value)"
            >
                <span
                    class="absolute inset-x-0 top-0 h-1"
                    :style="{ background: type.accent }"
                />

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Type de mission</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ type.label }}</p>
                    </div>
                    <span
                        class="inline-flex min-w-[2.5rem] items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="type.count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                    >
                        {{ type.count }}
                    </span>
                </div>

                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    {{ type.description }}
                </p>

                <p class="mt-4 text-sm font-medium text-emerald-700 group-hover:text-emerald-800">
                    Voir l'historique →
                </p>
            </button>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import { useMissionTypes } from '../../composables/useMissionTypes';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const { loadMissionTypes, types: missionTypes, loading: typesLoading } = useMissionTypes();

const loading = ref(true);
const missions = ref([]);
const error = ref('');

const missionCountByType = computed(() => {
    const counts = {};

    for (const mission of missions.value) {
        const key = mission.mission_type;
        if (!key) continue;
        counts[key] = (counts[key] ?? 0) + 1;
    }

    return counts;
});

const visibleTypes = computed(() => {
    const typesFromMissions = [...new Set(missions.value.map((mission) => mission.mission_type).filter(Boolean))];
    const profile = auth.user?.profile;
    const profileTypes = profile === 'metier' || profile === 'super_admin'
        ? missionTypes.value.map((type) => type.value)
        : missionTypes.value
            .filter((type) => (type.profiles ?? []).includes(profile))
            .map((type) => type.value);
    const typeValues = typesFromMissions.length ? typesFromMissions : profileTypes;

    return missionTypes.value
        .filter((type) => typeValues.includes(type.value))
        .map((type) => ({
            ...type,
            accent: type.accent_color ?? '#047857',
            description: type.description ?? 'Consulter les missions enregistrées pour ce type.',
            count: missionCountByType.value[type.value] ?? 0,
        }));
});

function extractMissions(data) {
    const root = data?.data ?? data;
    return Array.isArray(root) ? root : [];
}

function openHistory(missionType) {
    router.push({
        name: 'audit.missions.history.byType',
        params: { missionType },
    });
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

onMounted(async () => {
    await loadMissionTypes();
    await loadMissions();
});
</script>
