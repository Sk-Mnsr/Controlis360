<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold">Réponse — Action</h3>
            <p class="mt-1 text-sm text-slate-500">
                Choisissez comment traiter la mission {{ mission?.reference }}
            </p>

            <div v-if="loadingAgents" class="mt-6 text-sm text-slate-500">Chargement des agents...</div>

            <div v-else class="mt-6 space-y-4">
                <button
                    type="button"
                    class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left hover:border-emerald-300"
                    @click="$emit('choose', 'self')"
                >
                    <p class="font-semibold text-emerald-900">Traiter moi-même</p>
                    <p class="mt-1 text-sm text-emerald-800">Remplir le formulaire d'action et transmettre</p>
                </button>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="font-semibold text-slate-900">Affecter à un agent</p>
                    <p class="mt-1 text-sm text-slate-500">L'agent remplit le formulaire, vous validez avant envoi</p>

                    <select
                        v-model="selectedAgentId"
                        class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option :value="null" disabled>Sélectionner un agent</option>
                        <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                            {{ agent.name }}
                        </option>
                    </select>

                    <p v-if="!agents.length" class="mt-2 text-xs text-amber-700">
                        Aucun agent rattaché à votre entité pour cette mission.
                    </p>

                    <button
                        type="button"
                        class="mt-3 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50"
                        :disabled="!selectedAgentId"
                        @click="$emit('choose', 'agent', selectedAgentId)"
                    >
                        Affecter
                    </button>
                </div>
            </div>

            <button
                type="button"
                class="mt-6 text-sm text-slate-500 hover:text-slate-800"
                @click="$emit('close')"
            >
                Annuler
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../api/client';

const props = defineProps({
    open: { type: Boolean, default: false },
    mission: { type: Object, default: null },
});

defineEmits(['close', 'choose']);

const agents = ref([]);
const selectedAgentId = ref(null);
const loadingAgents = ref(false);

async function loadAgents() {
    if (!props.mission?.id) {
        return;
    }

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

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        selectedAgentId.value = null;
        loadAgents();
    }
});
</script>
