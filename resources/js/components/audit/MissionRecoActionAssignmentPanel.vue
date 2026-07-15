<template>
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Traitement de la réponse</h3>
            <p class="mt-1 text-sm text-slate-500">
                Choisissez comment traiter cette recommandation avant de remplir le plan d'action.
            </p>
        </div>

        <div v-if="loadingAgents" class="text-sm text-slate-500">Chargement des membres…</div>

        <div v-else class="space-y-3">
            <button
                type="button"
                class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left hover:border-emerald-300 disabled:opacity-60"
                :disabled="starting"
                @click="choose('self')"
            >
                <p class="font-semibold text-emerald-900">Traiter moi-même</p>
                <p class="mt-1 text-sm text-emerald-800">Remplir le plan d'action et transmettre à l'audit</p>
            </button>

            <div class="rounded-xl border border-slate-200 p-4">
                <p class="font-semibold text-slate-900">Affecter à un membre de l'équipe</p>
                <p class="mt-1 text-sm text-slate-500">
                    Le membre remplit le plan d'action, vous validez avant envoi à l'audit ou au contrôle.
                </p>

                <select
                    v-model="selectedAgentId"
                    class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option :value="null" disabled>Sélectionner un membre</option>
                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                        {{ agent.name }}
                    </option>
                </select>

                <p v-if="!agents.length" class="mt-2 text-xs text-amber-700">
                    Aucun membre rattaché à votre département pour cette mission.
                </p>

                <button
                    type="button"
                    class="mt-3 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50"
                    :disabled="!selectedAgentId || starting"
                    @click="choose('agent', selectedAgentId)"
                >
                    Affecter
                </button>
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
});

const emit = defineEmits(['assigned']);

const agents = ref([]);
const selectedAgentId = ref(null);
const loadingAgents = ref(false);
const starting = ref(false);
const error = ref('');

async function loadAgents() {
    if (!props.mission?.id) return;

    loadingAgents.value = true;

    try {
        const { data } = await api.get(`/missions/${props.mission.id}/agents`);
        agents.value = data?.data ?? data ?? [];
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
        fd.append('handling_mode', mode);
        if (mode === 'agent' && agentId) {
            fd.append('agent_id', String(agentId));
        }

        const { data } = await api.post(`/missions/${props.mission.id}/responses/action/start`, fd);
        emit('assigned', data?.data ?? data);
    } catch (err) {
        error.value = err.response?.data?.message?.[0]
            ?? err.response?.data?.agent_id?.[0]
            ?? 'Impossible d\'initialiser la réponse.';
    } finally {
        starting.value = false;
    }
}

watch(() => props.mission?.id, loadAgents, { immediate: true });
</script>
