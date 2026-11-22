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
            <button
                v-if="canReturn"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition"
                :class="view === 'retour'
                    ? 'bg-amber-700 text-white'
                    : 'border border-amber-300 bg-white text-amber-900 hover:bg-amber-50'"
                @click="view = 'retour'"
            >
                Retourner
            </button>
        </div>

        <MissionRecoReturnsNotice
            v-if="returns.length && view !== 'retour'"
            :returns="returns"
            :show-reassign-hint="reco.status === 'retournee'"
        />

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
                :recommendation-id="reco.id"
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

        <form v-else-if="view === 'retour'" class="space-y-4" @submit.prevent="submitReturn">
            <MissionRecoReturnsNotice v-if="returns.length" :returns="returns" />

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-900">Retourner cette recommandation</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Indiquez pourquoi elle ne concerne pas votre entité. Elle quittera votre liste et l'émetteur pourra la réaffecter.
                </p>

                <label class="mb-1 mt-4 block text-sm font-medium text-slate-700" for="reco-return-avis">
                    Avis
                </label>
                <textarea
                    id="reco-return-avis"
                    v-model="returnAvis"
                    rows="4"
                    required
                    minlength="5"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Expliquez pourquoi cette recommandation ne vous est pas destinée."
                />

                <p v-if="returnError" class="mt-3 text-sm text-red-600">{{ returnError }}</p>

                <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                    <button
                        type="submit"
                        class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800 disabled:opacity-60"
                        :disabled="returning"
                    >
                        Retourner la recommandation
                    </button>
                </div>
            </div>
        </form>

        <div
            v-if="returnConfirmOpen"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="returnConfirmOpen = false"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900">Retourner cette recommandation ?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Elle ne sera plus dans votre liste. L'émetteur recevra votre avis et pourra la réaffecter.
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        :disabled="returning"
                        @click="returnConfirmOpen = false"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800 disabled:opacity-60"
                        :disabled="returning"
                        @click="confirmReturn"
                    >
                        {{ returning ? 'Envoi…' : 'Retourner' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import MissionRecoActionAssignmentPanel from './MissionRecoActionAssignmentPanel.vue';
import MissionRecoActionPlanForm from './MissionRecoActionPlanForm.vue';
import MissionRecoActionPlanSummary from './MissionRecoActionPlanSummary.vue';
import MissionRecoCommentForm from './MissionRecoCommentForm.vue';
import MissionRecoResponseAttachmentsPanel from './MissionRecoResponseAttachmentsPanel.vue';
import MissionRecoReturnsNotice from './MissionRecoReturnsNotice.vue';

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
const returnAvis = ref('');
const returnError = ref('');
const returning = ref(false);
const returnConfirmOpen = ref(false);

const activeResponse = computed(() => localResponse.value ?? props.response ?? null);
const returns = computed(() => props.reco?.returns ?? []);
const canReturn = computed(() => (
    props.canAssignAction
    && ['emise', 'en_cours'].includes(props.reco?.status ?? 'emise')
));

const ownerPlanOwnerId = computed(() => {
    if (props.canAssignAction && auth.user?.profile !== 'super_admin') return auth.user?.id;
    return activeResponse.value?.responsable_id ?? activeResponse.value?.responsable?.id ?? auth.user?.id;
});

const hasOwnerActionPlans = computed(() => (
    (props.reco.action_plans ?? []).some(
        (plan) => Number(plan.user_id) === Number(ownerPlanOwnerId.value),
    )
));

const hasVisiblePlans = computed(() => (props.reco.action_plans ?? []).length > 0);

const waitingForAgent = computed(() => (
    props.canAssignAction
    && auth.user?.profile !== 'super_admin'
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

function returnErrorMessage(err) {
    const data = err.response?.data ?? {};
    const errors = data.errors;

    if (typeof errors === 'string' && errors.trim()) return errors;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat().find(Boolean);
        if (first) return String(first);
    }
    if (Array.isArray(data.message) && data.message[0]) return data.message[0];
    if (typeof data.message === 'string' && data.message.trim() && !data.message.includes('SQLSTATE')) {
        return data.message;
    }

    return 'Retour impossible.';
}

async function submitReturn() {
    const avis = returnAvis.value.trim();

    if (avis.length < 5) {
        returnError.value = 'Saisissez un avis d\'au moins 5 caractères.';
        return;
    }

    returnError.value = '';
    returnConfirmOpen.value = true;
}

async function confirmReturn() {
    const avis = returnAvis.value.trim();
    returning.value = true;
    returnError.value = '';

    try {
        await api.post(`/recommendations/${props.reco.id}/return`, { avis });
        returnAvis.value = '';
        returnConfirmOpen.value = false;
        emit('updated');
    } catch (err) {
        returnConfirmOpen.value = false;
        returnError.value = returnErrorMessage(err);
    } finally {
        returning.value = false;
    }
}

watch(() => props.reco?.id, () => {
    view.value = 'action';
    localResponse.value = null;
    assignmentDismissed.value = false;
    returnAvis.value = '';
    returnError.value = '';
    returnConfirmOpen.value = false;
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
