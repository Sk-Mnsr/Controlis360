<template>
    <section class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Tableau de bord</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Suivi des recommandations d’audit et de contrôle
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <RouterLink
                    v-if="canCreate"
                    :to="{ name: 'audit.missions.create' }"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                >
                    + Créer mission
                </RouterLink>
                <RouterLink
                    :to="{ name: 'audit.missions' }"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Synthèse par mission
                </RouterLink>
            </div>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement du tableau de bord...
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">
            {{ error }}
        </div>

        <AuditDashboardPanel
            v-else
            :missions="missions"
            from-query="dashboard"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../api/client';
import AuditDashboardPanel from '../../components/audit/AuditDashboardPanel.vue';
import { canCreateMission } from '../../config/module-access';
import { useMissionParametrage } from '../../composables/useMissionParametrage';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const { loadMissionParametrage } = useMissionParametrage();

const loading = ref(true);
const error = ref('');
const missions = ref([]);

const canCreate = computed(() => canCreateMission(auth.user));

function extractMissions(data) {
    const root = data?.data ?? data;
    return Array.isArray(root) ? root : [];
}

function apiErrorMessage(err, fallback) {
    const message = err.response?.data?.message;
    if (Array.isArray(message) && message.length) return String(message[0]);
    if (typeof message === 'string' && message.trim()) return message;
    return fallback;
}

async function loadDashboard() {
    loading.value = true;
    error.value = '';

    try {
        await loadMissionParametrage();
        const { data } = await api.get('/missions');
        missions.value = extractMissions(data);
    } catch (err) {
        error.value = apiErrorMessage(err, 'Impossible de charger le tableau de bord.');
    } finally {
        loading.value = false;
    }
}

onMounted(loadDashboard);
</script>
