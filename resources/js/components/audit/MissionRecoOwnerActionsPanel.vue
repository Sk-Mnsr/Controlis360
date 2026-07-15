<template>
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition"
                :class="view === 'action'
                    ? 'bg-emerald-700 text-white'
                    : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                @click="view = 'action'"
            >
                Action
            </button>
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition"
                :class="view === 'commentaire'
                    ? 'bg-emerald-700 text-white'
                    : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                @click="view = 'commentaire'"
            >
                Commentaires
            </button>
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition"
                :class="view === 'pieces'
                    ? 'bg-emerald-700 text-white'
                    : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                @click="view = 'pieces'"
            >
                Pièces justificatives
            </button>
            <button
                v-if="canAssignAction"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition"
                :class="view === 'affecter'
                    ? 'bg-emerald-700 text-white'
                    : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                @click="view = 'affecter'"
            >
                Affecter
            </button>
        </div>

        <template v-if="view === 'action'">
            <div
                v-if="waitingForAgent"
                class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                <p class="font-semibold">En attente du membre affecté</p>
                <p class="mt-1">
                    <strong>{{ activeResponse?.assigned_agent?.name ?? 'Le membre' }}</strong>
                    est en charge du plan d'action. Vous pourrez valider et transmettre une fois sa réponse soumise.
                </p>
            </div>

            <MissionRecoActionPlanForm
                v-else-if="showActionForm"
                :reco="reco"
                :mission="mission"
                :response="activeResponse"
                :action-plans="reco.action_plans ?? []"
                @updated="onFormUpdated"
            />

            <MissionRecoActionPlanSummary
                v-else-if="hasVisiblePlans"
                :plans="reco.action_plans ?? []"
                :reco-id="reco.id"
                :can-comment="true"
                @updated="$emit('updated')"
            />

            <p
                v-else-if="canAssignAction && !activeResponse?.id"
                class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
            >
                Commencez par l'onglet <strong>Affecter</strong> pour choisir le mode de traitement de cette recommandation.
            </p>

            <p v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Aucun plan d'action disponible pour le moment.
            </p>
        </template>

        <template v-else-if="view === 'affecter'">
            <MissionRecoActionAssignmentPanel
                v-if="showAssignmentPanel"
                :mission="mission"
                @assigned="onAssigned"
            />

            <div
                v-else-if="waitingForAgent"
                class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                <p class="font-semibold">Membre affecté</p>
                <p class="mt-1">
                    <strong>{{ activeResponse?.assigned_agent?.name ?? 'Le membre' }}</strong>
                    remplit le plan d'action. Vous pourrez valider et transmettre une fois sa réponse soumise.
                </p>
            </div>

            <div
                v-else-if="activeResponse?.handling_mode === 'self'"
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"
            >
                <p class="font-semibold">Traitement par vous-même</p>
                <p class="mt-1">
                    Vous traitez cette recommandation. Remplissez le plan d'action dans l'onglet <strong>Action</strong>.
                </p>
            </div>

            <p v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Le mode de traitement est déjà défini pour cette recommandation.
            </p>
        </template>

        <MissionRecoCommentForm
            v-else-if="view === 'commentaire'"
            :reco="reco"
            :follow-ups="reco.follow_ups ?? []"
            @saved="$emit('updated')"
        />

        <MissionRecoResponseAttachmentsPanel
            v-else-if="view === 'pieces'"
            :mission="mission"
            :response="activeResponse"
            @updated="onFormUpdated"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import MissionRecoActionAssignmentPanel from './MissionRecoActionAssignmentPanel.vue';
import MissionRecoActionPlanForm from './MissionRecoActionPlanForm.vue';
import MissionRecoActionPlanSummary from './MissionRecoActionPlanSummary.vue';
import MissionRecoCommentForm from './MissionRecoCommentForm.vue';
import MissionRecoResponseAttachmentsPanel from './MissionRecoResponseAttachmentsPanel.vue';

const props = defineProps({
    reco: { type: Object, required: true },
    mission: { type: Object, required: true },
    response: { type: Object, default: null },
    canEditAction: { type: Boolean, default: false },
    canAssignAction: { type: Boolean, default: false },
});

const emit = defineEmits(['updated']);

const auth = useAuthStore();
const view = ref('action');
const localResponse = ref(null);
const assignmentDismissed = ref(false);

const activeResponse = computed(() => localResponse.value ?? props.response ?? null);

const ownerPlanOwnerId = computed(() => {
    if (props.canAssignAction) return auth.user?.id;
    return activeResponse.value?.responsable_id ?? activeResponse.value?.responsable?.id ?? null;
});

const hasOwnerActionPlans = computed(() => (
    (props.reco.action_plans ?? []).some(
        (plan) => Number(plan.user_id) === Number(ownerPlanOwnerId.value),
    )
));

const hasVisiblePlans = computed(() => (props.reco.action_plans ?? []).length > 0);

const waitingForAgent = computed(() => (
    props.canAssignAction
    && activeResponse.value?.handling_mode === 'agent'
    && activeResponse.value?.workflow_status === 'en_saisie'
));

const showAssignmentPanel = computed(() => {
    if (!props.canAssignAction || assignmentDismissed.value) return false;

    const response = activeResponse.value;

    if (!response?.id) return true;

    if (response.handling_mode === 'agent') return false;
    if (response.workflow_status !== 'en_saisie') return false;

    return !hasOwnerActionPlans.value;
});

const showActionForm = computed(() => {
    if (!props.canEditAction) return false;
    if (!activeResponse.value?.id) return false;
    if (waitingForAgent.value) return false;

    return Boolean(activeResponse.value?.can_edit);
});

function onAssigned(response) {
    localResponse.value = response;
    assignmentDismissed.value = true;
    emit('updated', response);
    view.value = response?.handling_mode === 'agent' ? 'affecter' : 'action';
}

function onFormUpdated(payload) {
    if (payload?.id) {
        localResponse.value = payload;
        assignmentDismissed.value = true;
    }
    emit('updated', payload);
}

watch(() => props.reco?.id, () => {
    view.value = 'action';
    localResponse.value = null;
    assignmentDismissed.value = false;
});

watch(() => props.response, (value) => {
    if (value?.id) {
        localResponse.value = value;
        if (value.handling_mode === 'agent' || hasOwnerActionPlans.value) {
            assignmentDismissed.value = true;
        }
    }
}, { immediate: true });
</script>
