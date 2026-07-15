<template>
    <section class="space-y-6">
        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement...
        </div>

        <form v-else class="space-y-6" @submit.prevent="save">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Jours restants</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Définissez le seuil d'alerte et les couleurs affichées selon le nombre de jours restants.
                </p>

                <div class="mt-4 max-w-xs">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Seuil « proche échéance » (jours)</label>
                    <input
                        v-model.number="form.deadline_rules.near_threshold_days"
                        type="number"
                        min="0"
                        max="365"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Règle</th>
                                <th class="px-4 py-3">Libellé statut</th>
                                <th class="px-4 py-3">Couleur texte (jours)</th>
                                <th class="px-4 py-3">Couleur badge statut</th>
                                <th class="px-4 py-3">Aperçu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in form.deadline_rules.items" :key="item.key">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ ruleLabel(item.key) }}</td>
                                <td class="px-4 py-3">
                                    <input v-model="item.label" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                                </td>
                                <td class="px-4 py-3">
                                    <input v-model="item.text_color" type="color" class="h-10 w-full rounded-lg border border-slate-300 px-1 py-1" />
                                </td>
                                <td class="px-4 py-3">
                                    <input v-model="item.badge_color" type="color" class="h-10 w-full rounded-lg border border-slate-300 px-1 py-1" />
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm" :style="textColorStyle(item.text_color)">12</span>
                                    <span class="ml-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :style="solidBadgeStyle(item.badge_color)">{{ item.label }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Statuts mission</h3>
                        <p class="mt-1 text-sm text-slate-500">Couleurs des badges de statut affichés dans les listes de missions.</p>
                    </div>
                    <button type="button" class="text-sm font-medium text-emerald-700" @click="addMissionStatus">+ Ajouter</button>
                </div>

                <div class="mt-4 space-y-3">
                    <div
                        v-for="(status, index) in form.statuses"
                        :key="`mission-${status.value}-${index}`"
                        class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[1fr_1fr_1fr_auto_auto]"
                    >
                        <input v-model="status.value" type="text" required placeholder="Code" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono" />
                        <input v-model="status.label" type="text" required placeholder="Libellé" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <input v-model="status.color" type="color" class="h-10 w-full rounded-lg border border-slate-300 px-1 py-1" />
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :style="statusBadgeStyle(status.color)">{{ status.label }}</span>
                        <button type="button" class="text-sm text-red-600" @click="removeMissionStatus(index)">Supprimer</button>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Statuts recommandation</h3>
                        <p class="mt-1 text-sm text-slate-500">Couleurs des statuts de recommandation dans les tableaux et fiches.</p>
                    </div>
                    <button type="button" class="text-sm font-medium text-emerald-700" @click="addRecommendationStatus">+ Ajouter</button>
                </div>

                <div class="mt-4 space-y-3">
                    <div
                        v-for="(status, index) in form.recommendation_statuses"
                        :key="`reco-${status.value}-${index}`"
                        class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[1fr_1fr_1fr_auto_auto]"
                    >
                        <input v-model="status.value" type="text" required placeholder="Code" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono" />
                        <input v-model="status.label" type="text" required placeholder="Libellé" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <input v-model="status.color" type="color" class="h-10 w-full rounded-lg border border-slate-300 px-1 py-1" />
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :style="statusBadgeStyle(status.color)">{{ status.label }}</span>
                        <button type="button" class="text-sm text-red-600" @click="removeRecommendationStatus(index)">Supprimer</button>
                    </div>
                </div>
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer les couleurs' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useMissionParametrage } from '../../composables/useMissionParametrage';
import {
    normalizeParametrageColors,
    solidBadgeStyle,
    statusBadgeStyle,
    textColorStyle,
} from '../../utils/mission-display-styles';

const { loadMissionParametrage, saveMissionParametrage, loading } = useMissionParametrage();

const saving = ref(false);
const error = ref('');

const form = reactive({
    statuses: [],
    recommendation_statuses: [],
    deadline_rules: {
        near_threshold_days: 10,
        items: [],
    },
});

const RULE_LABELS = {
    late: 'En retard (jours < 0)',
    warning: 'Proche échéance',
    ok: 'Dans les délais',
    neutral: 'Non renseigné',
};

function ruleLabel(key) {
    return RULE_LABELS[key] ?? key;
}

function fillForm(data) {
    const normalized = normalizeParametrageColors(data);
    form.statuses = (normalized?.statuses ?? []).map((item) => ({ ...item }));
    form.recommendation_statuses = (normalized?.recommendation_statuses ?? []).map((item) => ({ ...item }));
    form.deadline_rules = {
        near_threshold_days: normalized?.deadline_rules?.near_threshold_days ?? 10,
        items: (normalized?.deadline_rules?.items ?? []).map((item) => ({ ...item })),
    };
}

function addMissionStatus() {
    form.statuses.push({ value: '', label: '', color: '#64748b' });
}

function removeMissionStatus(index) {
    form.statuses.splice(index, 1);
}

function addRecommendationStatus() {
    form.recommendation_statuses.push({ value: '', label: '', color: '#64748b' });
}

function removeRecommendationStatus(index) {
    form.recommendation_statuses.splice(index, 1);
}

async function save() {
    saving.value = true;
    error.value = '';

    try {
        const data = await saveMissionParametrage({
            statuses: form.statuses,
            recommendation_statuses: form.recommendation_statuses,
            deadline_rules: form.deadline_rules,
        });
        fillForm(data);
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : err.response?.data?.message?.[0] ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    const data = await loadMissionParametrage();
    fillForm(data);
});
</script>
