<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Qui traite cette recommandation ?</h3>
            <p class="mt-1 text-sm text-slate-500">
                Ce choix prépare le plan d'action. Il ne concerne que cette recommandation.
            </p>
        </div>

        <p v-if="loadingAgents" class="text-sm text-slate-500">Chargement des membres…</p>

        <div v-else class="grid gap-3 lg:grid-cols-2">
            <button
                type="button"
                class="flex h-full flex-col items-start rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-left transition hover:border-emerald-400 hover:bg-emerald-100 disabled:opacity-60"
                :disabled="starting"
                @click="choose('self')"
            >
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 12Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 19.5a6.5 6.5 0 0 1 13 0" />
                    </svg>
                </span>
                <span class="mt-3 font-semibold text-emerald-950">Traiter moi-même</span>
                <span class="mt-1 text-sm text-emerald-900">Rédiger le plan d'action, puis le transmettre.</span>
            </button>

            <div class="flex h-full flex-col rounded-xl border border-slate-200 bg-white px-4 py-4">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-800 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 11a2.5 2.5 0 1 0-2.5-2.5A2.5 2.5 0 0 0 8.5 11Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 18.5a5 5 0 0 1 8.2-1.6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a2.5 2.5 0 1 0-2.5-2.5A2.5 2.5 0 0 0 16 11Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.2 16.9a5 5 0 0 1 6.3 1.6" />
                    </svg>
                </span>
                <p class="mt-3 font-semibold text-slate-900">Affecter un membre</p>
                <p class="mt-1 text-sm text-slate-500">
                    Le membre prépare le plan. Vous le validez avant de le transmettre.
                </p>

                <div v-if="agents.length" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <select
                        v-model="selectedAgentId"
                        class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800"
                        :disabled="starting"
                    >
                        <option :value="null" disabled>Sélectionner un membre</option>
                        <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                            {{ agent.name }}
                        </option>
                    </select>
                    <button
                        type="button"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!selectedAgentId || starting"
                        @click="choose('agent', selectedAgentId)"
                    >
                        {{ starting ? 'Affectation…' : 'Affecter' }}
                    </button>
                </div>

                <p v-else class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                    Aucun membre n'est rattaché aux départements de cette mission.
                </p>
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../api/client';

const props = defineProps({
    mission: { type: Object, required: true },
    recommendationId: { type: [Number, String], required: true },
});

const emit = defineEmits(['assigned']);

const agents = ref([]);
const selectedAgentId = ref(null);
const loadingAgents = ref(false);
const starting = ref(false);
const error = ref('');

function apiError(err, fallback) {
    const data = err.response?.data ?? {};
    const errors = data.errors;

    if (typeof errors === 'string' && errors.trim()) return errors;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat().find(Boolean);
        if (first) return String(first);
    }
    if (Array.isArray(data.message) && data.message[0]) return data.message[0];
    if (typeof data.message === 'string' && data.message.trim()) return data.message;

    return fallback;
}

async function loadAgents() {
    if (!props.mission?.id) return;

    loadingAgents.value = true;

    try {
        const { data } = await api.get(`/missions/${props.mission.id}/agents`);
        const payload = data?.data ?? data ?? [];
        agents.value = Array.isArray(payload) ? payload : [];
    } catch {
        agents.value = [];
    } finally {
        loadingAgents.value = false;
    }
}

async function choose(mode, agentId = null) {
    starting.value = true;
    error.value = '';

    try {
        const fd = new FormData();
        fd.append('recommendation_id', String(props.recommendationId));
        fd.append('handling_mode', mode);
        if (mode === 'agent' && agentId) {
            fd.append('agent_id', String(agentId));
        }

        const { data } = await api.post(`/missions/${props.mission.id}/responses/action/start`, fd);
        emit('assigned', data?.data ?? data);
    } catch (err) {
        error.value = apiError(err, 'Impossible d\'initialiser la réponse.');
    } finally {
        starting.value = false;
    }
}

watch(() => props.mission?.id, loadAgents, { immediate: true });
</script>
