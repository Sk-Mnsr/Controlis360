<template>
    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex min-h-[4rem] items-center justify-center bg-red-800 px-4 py-3 text-center text-sm font-semibold uppercase tracking-wide text-white">
            Résumé de mes recommandations
        </div>

        <div v-if="!recommendations.length" class="flex flex-1 items-center justify-center p-8 text-center text-sm text-slate-500">
            Aucune recommandation ne vous est assignée sur cette mission.
        </div>

        <div v-else class="flex-1 divide-y divide-slate-100 overflow-y-auto">
            <div
                v-for="(reco, index) in recommendations"
                :key="reco.id"
                class="p-5"
            >
                <p
                    v-if="recommendations.length > 1"
                    class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                    Recommandation {{ index + 1 }}
                </p>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <span class="text-slate-600">Référence :</span>
                        <span class="font-bold text-slate-900">{{ reco.reference || '—' }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Département(s) :</span>
                        <span class="font-bold text-slate-900">{{ departmentNames(reco) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Thème :</span>
                        <span class="font-bold text-slate-900">{{ reco.theme || '—' }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Priorité :</span>
                        <span class="font-bold text-slate-900">{{ reco.priority_fr || '—' }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Type de risque :</span>
                        <span class="font-bold text-slate-900">{{ reco.risk_type || '—' }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Échéance :</span>
                        <span class="font-bold text-slate-900">{{ formatDate(reco.due_date) }}</span>
                    </li>
                    <li>
                        <span class="text-slate-600">Statut :</span>
                        <span class="font-bold text-slate-900">{{ reco.status_fr ?? reco.status ?? '—' }}</span>
                    </li>
                    <li v-if="reco.recommendation_label">
                        <span class="text-slate-600">Recommandation :</span>
                        <span class="font-bold text-slate-900">{{ reco.recommendation_label }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { recommendationDepartments } from '../../utils/reco-stats';

defineProps({
    recommendations: { type: Array, default: () => [] },
});

function departmentNames(reco) {
    const names = recommendationDepartments(reco)
        .map((department) => department.name)
        .filter(Boolean);

    return names.length ? names.join(', ') : '—';
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}
</script>
