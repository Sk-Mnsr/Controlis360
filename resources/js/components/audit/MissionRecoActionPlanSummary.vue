<template>
    <div class="space-y-4">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-amber-300 text-left text-slate-900">
                        <th class="px-3 py-2 font-semibold">N°</th>
                        <th class="px-3 py-2 font-semibold">Plan d'action</th>
                        <th class="px-3 py-2 font-semibold">Responsable</th>
                        <th class="px-3 py-2 font-semibold">Échéance</th>
                        <th class="px-3 py-2 font-semibold">Statut</th>
                        <th class="px-3 py-2 font-semibold">Date transmission</th>
                        <th class="px-3 py-2 font-semibold">Retard (j)</th>
                        <th class="px-3 py-2 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!plans.length">
                        <td colspan="8" class="px-3 py-6 text-center text-slate-500">
                            Aucun plan d'action transmis.
                        </td>
                    </tr>
                    <tr
                        v-for="plan in plans"
                        :key="plan.id"
                        class="border-t border-slate-200"
                    >
                        <td class="px-3 py-2 align-top">{{ plan.line_number }}</td>
                        <td class="px-3 py-2 align-top whitespace-pre-wrap">{{ plan.action_plan }}</td>
                        <td class="px-3 py-2 align-top">{{ plan.responsible_name || '—' }}</td>
                        <td class="px-3 py-2 align-top">{{ formatDate(plan.due_date) }}</td>
                        <td class="px-3 py-2 align-top">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="statusClasses(plan.status_color)"
                            >
                                <span class="h-2 w-2 rounded-full" :class="dotClasses(plan.status_color)" />
                                {{ plan.status_fr }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">{{ formatDate(plan.transmission_date) }}</td>
                        <td class="px-3 py-2 align-top">{{ formatDelay(plan.delay_days) }}</td>
                        <td class="px-3 py-2 align-top text-center">
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition"
                                :class="detailPlanId === plan.id
                                    ? 'bg-emerald-700 text-white'
                                    : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                                title="Actions"
                                @click="openPlanDetail(plan)"
                            >
                                <span class="sr-only">Actions</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <circle cx="10" cy="4" r="1.5" />
                                    <circle cx="10" cy="10" r="1.5" />
                                    <circle cx="10" cy="16" r="1.5" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <MissionRecoActionPlanDetailPanel
            :open="detailOpen"
            :plan="detailPlan"
            :can-edit="false"
            :can-edit-attachments="false"
            :can-comment="canComment"
            @close="closePlanDetail"
            @view="openViewFromDetail"
            @updated="onDetailUpdated"
        />

        <MissionRecoActionPlanModal
            :open="modalOpen"
            mode="view"
            :plan="selectedPlan"
            :reco-id="recoId"
            @close="modalOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { actionPlanStatusClasses } from '../../config/mission-parametrage';
import MissionRecoActionPlanModal from './MissionRecoActionPlanModal.vue';
import MissionRecoActionPlanDetailPanel from './MissionRecoActionPlanDetailPanel.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    canComment: { type: Boolean, default: true },
    recoId: { type: Number, default: 0 },
});

const emit = defineEmits(['updated']);

const modalOpen = ref(false);
const selectedPlan = ref(null);
const detailOpen = ref(false);
const detailPlan = ref(null);

const detailPlanId = computed(() => detailPlan.value?.id ?? null);

watch(() => props.plans, (plans) => {
    if (!detailPlan.value?.id) return;
    const updated = (plans ?? []).find((plan) => Number(plan.id) === Number(detailPlan.value.id));
    if (updated) {
        detailPlan.value = updated;
    } else {
        closePlanDetail();
    }
});

function openPlanDetail(plan) {
    detailPlan.value = plan;
    detailOpen.value = true;
}

function closePlanDetail() {
    detailOpen.value = false;
    detailPlan.value = null;
}

function openView(plan) {
    selectedPlan.value = plan;
    modalOpen.value = true;
}

function openViewFromDetail(plan) {
    openView(plan);
}

function onDetailUpdated(payload) {
    if (payload?.id && detailPlan.value && Number(payload.id) === Number(detailPlan.value.id)) {
        detailPlan.value = { ...detailPlan.value, ...payload };
    }
    emit('updated', payload);
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatDelay(value) {
    if (value === null || value === undefined) return '—';
    return value;
}

function statusClasses(color) {
    return actionPlanStatusClasses(color);
}

function dotClasses(color) {
    return {
        blue: 'bg-blue-500',
        amber: 'bg-amber-500',
        orange: 'bg-orange-500',
        red: 'bg-red-500',
        emerald: 'bg-emerald-500',
        slate: 'bg-slate-500',
    }[color] ?? 'bg-slate-400';
}
</script>
