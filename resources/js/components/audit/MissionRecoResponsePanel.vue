<template>
    <MissionRecoDetailPanel
        v-if="!responses.length && reco"
        :reco="reco"
        :show-detail-link="showDetailLink"
        :show-owners="showOwners"
        @view-detail="$emit('view-detail')"
    />

    <div v-else-if="!responses.length" class="rounded-lg border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
        Aucune réponse enregistrée pour cette recommandation.
    </div>

    <div v-else class="space-y-3">
        <div
            v-for="response in responses"
            :key="response.id"
            class="rounded-lg border border-emerald-200 bg-emerald-50/40 p-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Réponse — {{ response.response_type_fr }}
                    </h3>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs text-emerald-800">
                        {{ response.workflow_status_fr }}
                    </span>
                </div>
                <button
                    v-if="showDetailLink"
                    type="button"
                    class="text-xs font-medium text-emerald-800 hover:underline"
                    @click="$emit('view-detail')"
                >
                    Voir le détail reco
                </button>
            </div>

            <template v-if="response.response_type === 'passivite'">
                <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ response.passivity_comment }}</p>
            </template>
            <template v-else>
                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                    <DetailRow label="OWNERS" :value="response.responsible_name" />
                    <DetailRow label="Avancement" :value="progressLabel(response.progress_rate)" />
                    <DetailRow label="Début" :value="formatDate(response.action_start_date)" />
                    <DetailRow label="Fin prévue" :value="formatDate(response.planned_end_date)" />
                    <DetailRow label="Go / No Go" :value="response.go_no_go_fr" />
                    <DetailRow label="Investissement" :value="formatAmount(response.investment_amount)" />
                    <DetailRow
                        label="Infrastructure"
                        :value="response.needs_infrastructure_change ? 'Oui' : 'Non'"
                    />
                    <DetailRow
                        v-if="response.action_plan"
                        label="Plan d'action"
                        :value="response.action_plan"
                    />
                    <DetailRow
                        v-if="response.comment"
                        label="Commentaire"
                        :value="response.comment"
                    />
                </dl>
            </template>

            <button
                v-if="response.can_validate"
                type="button"
                class="mt-4 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800"
                @click="$emit('validate', response)"
            >
                Valider et clôturer
            </button>
        </div>
    </div>
</template>

<script setup>
import DetailRow from './DetailRow.vue';
import MissionRecoDetailPanel from './MissionRecoDetailPanel.vue';

defineProps({
    reco: { type: Object, default: null },
    responses: { type: Array, default: () => [] },
    showDetailLink: { type: Boolean, default: false },
    showOwners: { type: Boolean, default: true },
});

defineEmits(['validate', 'view-detail']);

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatAmount(value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${Number(value).toLocaleString('fr-FR')} FCFA`;
}

function progressLabel(value) {
    if (value === null || value === undefined) return '—';
    return `${value} %`;
}
</script>
