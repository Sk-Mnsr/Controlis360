<template>
    <div class="space-y-4">
        <div
            v-if="isResponsable && response?.handling_mode === 'self' && response?.workflow_status === 'en_saisie'"
            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
        >
            Vous traitez cette réponse vous-même.
            La <strong>date de transmission</strong> est renseignée automatiquement à la date du jour lors de l'enregistrement.
            Cliquez <strong>Transmettre</strong> pour envoyer le plan à l'audit ou au contrôle.
        </div>

        <div
            v-else-if="isResponsable && response?.workflow_status === 'a_valider'"
            class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
        >
            Réponse soumise par
            <strong>{{ response?.assigned_agent?.name ?? 'le membre' }}</strong>.
            Vérifiez le plan d'action puis cliquez <strong>Valider et transmettre</strong>.
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Plan d'action</h3>
            <div class="flex flex-wrap gap-2">
                <button
                    v-if="canEdit"
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 text-lg font-semibold text-emerald-800 hover:bg-emerald-100"
                    title="Ajouter une ligne"
                    @click="openCreate"
                >
                    +
                </button>
                <button
                    v-if="response?.can_submit_agent"
                    type="button"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-60"
                    :disabled="saving"
                    @click="submitToResponsable"
                >
                    Soumettre au responsable
                </button>
                <button
                    v-if="response?.can_forward"
                    type="button"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-60"
                    :disabled="saving"
                    @click="transmit"
                >
                    {{ forwardLabel }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-amber-300 text-left text-slate-900">
                        <th class="px-2 py-2 font-semibold">N°</th>
                        <th class="min-w-[12rem] px-2 py-2 font-semibold">Plan d'action</th>
                        <th class="px-2 py-2 font-semibold">Responsable</th>
                        <th class="px-2 py-2 font-semibold">Échéance</th>
                        <th class="px-2 py-2 font-semibold">Statut</th>
                        <th class="px-2 py-2 font-semibold">Date transmission</th>
                        <th class="px-2 py-2 font-semibold">Retard (j)</th>
                        <th class="px-2 py-2 font-semibold">En attente</th>
                        <th class="px-2 py-2 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!displayPlans.length">
                        <td colspan="9" class="px-3 py-6 text-center text-slate-500">
                            Aucune ligne de plan d'action.
                        </td>
                    </tr>
                    <tr
                        v-for="plan in displayPlans"
                        :key="plan.id"
                        class="border-t border-slate-200"
                    >
                        <td class="px-2 py-2 align-top">{{ plan.line_number }}</td>
                        <td class="px-2 py-2 align-top whitespace-pre-wrap">{{ plan.action_plan }}</td>
                        <td class="px-2 py-2 align-top">{{ plan.responsible_name || '—' }}</td>
                        <td class="px-2 py-2 align-top">{{ formatDisplayDate(plan.due_date) }}</td>
                        <td class="px-2 py-2 align-top">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusClassesForValue(plan.status)"
                            >
                                <span class="h-2 w-2 rounded-full" :class="dotClassesForValue(plan.status_color)" />
                                {{ plan.status_fr }}
                            </span>
                        </td>
                        <td class="px-2 py-2 align-top">{{ formatDisplayDate(plan.transmission_date) }}</td>
                        <td class="px-2 py-2 align-top">{{ formatDelay(plan.delay_days) }}</td>
                        <td class="px-2 py-2 align-top">{{ plan.is_waiting ? 'Oui' : '—' }}</td>
                        <td class="px-2 py-2 align-top text-center">
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
            :can-edit="detailPlan ? canEditPlan(detailPlan) : false"
            :can-edit-attachments="detailPlan ? canEditPlanAttachments(detailPlan) : false"
            :can-comment="true"
            @close="closePlanDetail"
            @view="openViewFromDetail"
            @edit="openEditFromDetail"
            @delete="removePlanFromDetail"
            @updated="onDetailUpdated"
        />

        <MissionRecoActionPlanModal
            :open="modalOpen"
            :mode="modalMode"
            :plan="selectedPlan"
            :reco-id="reco.id"
            :default-responsible-name="auth.user?.name ?? ''"
            :next-line-number="nextLineNumber"
            @close="closeModal"
            @saved="$emit('updated')"
        />

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="saving" class="text-sm text-slate-500">Traitement…</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { ACTION_PLAN_STATUSES, actionPlanStatusClasses } from '../../config/mission-parametrage';
import { isMissionResponsible } from '../../config/module-access';
import MissionRecoActionPlanModal from './MissionRecoActionPlanModal.vue';
import MissionRecoActionPlanDetailPanel from './MissionRecoActionPlanDetailPanel.vue';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    reco: { type: Object, required: true },
    mission: { type: Object, required: true },
    response: { type: Object, default: null },
    actionPlans: { type: Array, default: () => [] },
});

const emit = defineEmits(['updated']);

const auth = useAuthStore();
const saving = ref(false);
const error = ref('');
const modalOpen = ref(false);
const modalMode = ref('view');
const selectedPlan = ref(null);
const detailOpen = ref(false);
const detailPlan = ref(null);

const detailPlanId = computed(() => detailPlan.value?.id ?? null);

const isResponsable = computed(() => isMissionResponsible(auth.user));
const canEdit = computed(() => Boolean(props.response?.can_edit));

const planOwnerId = computed(() => {
    if (isResponsable.value) return auth.user?.id;
    return props.response?.responsable_id ?? props.response?.responsable?.id ?? null;
});

const forwardLabel = computed(() => (
    props.response?.handling_mode === 'agent' && props.response?.workflow_status === 'a_valider'
        ? 'Valider et transmettre'
        : 'Transmettre'
));

const ownerPlans = computed(() => (props.actionPlans ?? []).filter(
    (plan) => Number(plan.user_id) === Number(planOwnerId.value),
));

const displayPlans = computed(() => [...ownerPlans.value].sort(
    (a, b) => Number(a.line_number) - Number(b.line_number),
));

const nextLineNumber = computed(() => {
    if (!displayPlans.value.length) return 1;
    return Math.max(...displayPlans.value.map((plan) => Number(plan.line_number) || 0)) + 1;
});

watch(() => props.actionPlans, (plans) => {
    if (!detailPlan.value?.id) return;
    const updated = (plans ?? []).find((plan) => Number(plan.id) === Number(detailPlan.value.id));
    if (updated) {
        detailPlan.value = updated;
    } else {
        closePlanDetail();
    }
});

function canEditPlan(plan) {
    if (!canEdit.value) return false;
    if (plan.can_edit === false) return false;
    return true;
}

function canEditPlanAttachments(plan) {
    if (plan.can_edit_attachments === true) return true;
    return canEditPlan(plan);
}

function openPlanDetail(plan) {
    detailPlan.value = plan;
    detailOpen.value = true;
}

function closePlanDetail() {
    detailOpen.value = false;
    detailPlan.value = null;
}

function onDetailUpdated(payload) {
    if (payload?.id && detailPlan.value && Number(payload.id) === Number(detailPlan.value.id)) {
        detailPlan.value = { ...detailPlan.value, ...payload };
    }
    emit('updated', payload);
}

function openView(plan) {
    selectedPlan.value = plan;
    modalMode.value = 'view';
    modalOpen.value = true;
}

function openEdit(plan) {
    selectedPlan.value = plan;
    modalMode.value = 'edit';
    modalOpen.value = true;
}

function openViewFromDetail(plan) {
    openView(plan);
}

function openEditFromDetail(plan) {
    openEdit(plan);
}

function openCreate() {
    selectedPlan.value = null;
    modalMode.value = 'create';
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    selectedPlan.value = null;
}

function statusClassesForValue(status) {
    const item = ACTION_PLAN_STATUSES.find((entry) => entry.value === status);
    return actionPlanStatusClasses(item?.color);
}

function dotClassesForValue(color) {
    return dotClasses(color);
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

function formatDisplayDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatDelay(value) {
    if (value === null || value === undefined) return '—';
    return value;
}

async function removePlan(plan) {
    if (!plan?.id || !window.confirm('Supprimer cette ligne du plan d\'action ?')) return;

    saving.value = true;
    error.value = '';

    try {
        await api.delete(`/recommendations/action-plans/${plan.id}`);
        emit('updated');
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Suppression impossible.';
    } finally {
        saving.value = false;
    }
}

async function removePlanFromDetail(plan) {
    await removePlan(plan);
    if (!error.value) {
        closePlanDetail();
    }
}

async function submitToResponsable() {
    if (!props.response?.id || !props.response?.can_submit_agent) return;

    if (!displayPlans.value.length) {
        error.value = 'Ajoutez au moins une ligne de plan d\'action.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.post(
            `/missions/${props.mission.id}/responses/${props.response.id}/submit-agent`,
        );
        emit('updated', data?.data ?? data);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Soumission impossible.';
    } finally {
        saving.value = false;
    }
}

async function transmit() {
    if (!props.response?.id || !props.response?.can_forward) return;

    if (!displayPlans.value.length) {
        error.value = 'Ajoutez au moins une ligne de plan d\'action.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.post(
            `/missions/${props.mission.id}/responses/${props.response.id}/forward`,
        );
        emit('updated', data?.data ?? data);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Transmission impossible.';
    } finally {
        saving.value = false;
    }
}
</script>
