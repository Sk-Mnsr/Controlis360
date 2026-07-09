<template>
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">
                Détail — {{ reco.reference }}
            </h3>
            <button
                v-if="showDetailLink"
                type="button"
                class="text-xs font-medium text-emerald-800 hover:underline"
                @click="$emit('view-detail')"
            >
                Ouvrir en pleine page
            </button>
        </div>

        <div class="mt-4 rounded-xl border border-emerald-100 bg-white p-4">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Informations</h4>
            <ul class="mt-3 space-y-2 text-sm">
                <li>
                    <span class="text-slate-600">Référence :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.reference) }}</span>
                </li>
                <li>
                    <span class="text-slate-600">Département(s) :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.entity_name) }}</span>
                </li>
                <li>
                    <span class="text-slate-600">Priorité :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.priority_fr) }}</span>
                </li>
                <li>
                    <span class="text-slate-600">Niveau de risque :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.risk_level_fr) }}</span>
                </li>
                <li>
                    <span class="text-slate-600">Statut :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.status_fr ?? reco.status) }}</span>
                </li>
                <li v-if="showOwners">
                    <span class="text-slate-600">Conserne :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.concerned_names) }}</span>
                </li>
                <li v-if="showOwners">
                    <span class="text-slate-600">OWNERS :</span>
                    <span class="font-bold text-slate-900">{{ displayValue(reco.responsible_name) }}</span>
                </li>
                <li>
                    <span class="text-slate-600">Date recommandation :</span>
                    <span class="font-bold text-slate-900">{{ formatDate(reco.recommendation_date) }}</span>
                </li>
                <li>
                    <span class="text-slate-600">Échéance :</span>
                    <span class="font-bold text-slate-900">{{ formatDate(reco.due_date) }}</span>
                </li>
            </ul>
        </div>

        <div class="mt-4 space-y-3">
            <div v-if="reco.theme" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Thème</h4>
                <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ reco.theme }}</p>
            </div>

            <div v-if="reco.risk_type" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Risque</h4>
                <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ reco.risk_type }}</p>
            </div>

            <div v-if="reco.recommendation_label" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Recommandation</h4>
                <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ reco.recommendation_label }}</p>
            </div>

            <div v-if="reco.comments" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Commentaires</h4>
                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-800">{{ reco.comments }}</p>
            </div>

            <div v-if="reco.attachment_paths?.length" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <h4 class="text-sm font-semibold text-slate-700">Pièces jointes</h4>
                <ul class="mt-2 space-y-1 text-sm text-slate-800">
                    <li v-for="path in reco.attachment_paths" :key="path">
                        {{ fileName(path) }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    reco: { type: Object, required: true },
    showDetailLink: { type: Boolean, default: false },
    showOwners: { type: Boolean, default: true },
});

defineEmits(['view-detail']);

function displayValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    return value;
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function fileName(path) {
    if (!path) return '—';
    return String(path).split('/').pop();
}
</script>
