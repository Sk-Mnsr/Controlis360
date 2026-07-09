<template>
    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Paramétrage suivi-reco</h2>
            <p class="mt-1 text-sm text-slate-500">
                Gérez les types de mission, les couleurs des jours restants et des statuts.
            </p>

            <div class="mt-5 flex flex-wrap gap-2 border-b border-slate-200 pb-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-t-lg px-4 py-2 text-sm font-medium transition"
                    :class="activeTab === tab.key
                        ? 'border-b-2 border-emerald-600 text-emerald-700'
                        : 'text-slate-500 hover:text-slate-800'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <MissionTypesParametrageView v-if="activeTab === 'types'" />
        <MissionColorsParametrageView v-else />
    </section>
</template>

<script setup>
import { ref } from 'vue';
import MissionColorsParametrageView from './MissionColorsParametrageView.vue';
import MissionTypesParametrageView from './MissionTypesParametrageView.vue';

const tabs = [
    { key: 'types', label: 'Types de mission' },
    { key: 'colors', label: 'Couleurs & statuts' },
];

const activeTab = ref('types');
</script>
