<template>
    <div
        v-if="open"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div class="min-w-0">
                    <p v-if="missionReference" class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        {{ missionReference }}
                    </p>
                    <h3 class="truncate text-lg font-bold text-slate-900">
                        {{ recommendation?.reference ?? 'Recommandation' }}
                    </h3>
                    <div v-if="recommendation" class="mt-2 flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ displayValue(recommendation.status_fr ?? recommendation.status) }}
                        </span>
                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                            Priorité {{ displayValue(recommendation.priority_fr) }}
                        </span>
                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-800">
                            Risque {{ displayValue(recommendation.risk_level_fr) }}
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <button
                        v-if="showTransmitButton"
                        type="button"
                        class="rounded-lg border border-emerald-700 bg-white px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-50 disabled:opacity-60"
                        :disabled="false"
                        @click="openTransmitForm"
                    >
                        Transmettre
                    </button>
                    <button
                        v-if="showCloseButton"
                        type="button"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                        :disabled="closing"
                        @click="closeRecommendation"
                    >
                        {{ closing ? 'Clôture…' : 'Clôturer' }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                        title="Fermer"
                        @click="$emit('close')"
                    >
                        ×
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex flex-1 items-center justify-center p-10 text-sm text-slate-500">
                Chargement…
            </div>

            <div v-else-if="loadError" class="flex flex-1 items-center justify-center p-10 text-sm text-red-600">
                {{ loadError }}
            </div>

            <div v-else-if="recommendation" class="flex-1 overflow-y-auto p-6">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div v-for="fact in facts" :key="fact.label" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ fact.label }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ fact.value }}</dd>
                    </div>
                </dl>

                <MissionRecoReturnsNotice
                    class="mt-4"
                    :returns="recommendation.returns ?? []"
                    :show-reassign-hint="recommendation.status === 'retournee' && recommendation.can_edit"
                />
                <RouterLink
                    v-if="recommendation.status === 'retournee' && recommendation.can_edit"
                    :to="{
                        name: 'audit.missions.recommendation.edit',
                        params: { id: recommendation.mission_id, recoId: recommendation.id },
                    }"
                    class="mt-3 inline-flex rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                    @click="$emit('close')"
                >
                    Réaffecter le département
                </RouterLink>

                <div v-if="contentBlocks.length" class="mt-5 grid gap-3 sm:grid-cols-2">
                    <article
                        v-for="block in contentBlocks"
                        :key="block.label"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
                        :class="block.wide ? 'sm:col-span-2' : ''"
                    >
                        <h4 class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ block.label }}</h4>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-slate-900">{{ block.value }}</p>
                    </article>
                </div>

                <div class="mt-5 space-y-5">

                    <MissionRecoTransmitRegulatorForm
                        v-if="transmitFormOpen && recommendation"
                        :reco-id="recommendation.id"
                        @transmitted="onTransmitted"
                        @cancel="transmitFormOpen = false"
                    />

                    <MissionRecoRegulatorCommentsPanel
                        v-if="showTransmissionComments"
                        variant="transmission"
                        title="Transmissions au régulateur"
                        :comments="recommendation.regulator_transmission_comments ?? []"
                        :show-empty="false"
                    />

                    <MissionRecoRegulatorCommentsPanel
                        v-if="showRegulatorComments"
                        :comments="regulatorAvisComments"
                        hint="Consultez cet avis avant de clôturer ou non la recommandation."
                    />

                    <div v-if="recommendation.attachment_paths?.length" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                        <h4 class="text-sm font-semibold text-slate-700">Pièces jointes</h4>
                        <ul class="mt-2 space-y-1 text-sm text-slate-800">
                            <li v-for="path in recommendation.attachment_paths" :key="path">
                                {{ fileName(path) }}
                            </li>
                        </ul>
                    </div>
                </div>

                <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import MissionRecoRegulatorCommentsPanel from './MissionRecoRegulatorCommentsPanel.vue';
import MissionRecoReturnsNotice from './MissionRecoReturnsNotice.vue';
import MissionRecoTransmitRegulatorForm from './MissionRecoTransmitRegulatorForm.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    recoId: { type: Number, default: null },
    missionReference: { type: String, default: '' },
});

const emit = defineEmits(['close', 'closed', 'transmitted']);

const auth = useAuthStore();
const loading = ref(false);
const closing = ref(false);
const transmitFormOpen = ref(false);
const loadError = ref('');
const error = ref('');
const recommendation = ref(null);

const facts = computed(() => {
    const reco = recommendation.value;
    if (!reco) return [];

    const items = [
        { label: 'Département', value: displayValue(reco.entity_name) },
        { label: 'Concerne', value: displayValue(reco.concerned_names) },
        { label: 'Owners', value: displayValue(reco.responsible_name) },
        { label: 'Date de recommandation', value: formatDate(reco.recommendation_date) },
        { label: 'Échéance', value: formatDate(reco.due_date) },
    ];

    if (reco.regulator_transmitted_at) {
        const author = reco.regulator_transmitted_by_name ? ` — ${reco.regulator_transmitted_by_name}` : '';
        items.push({
            label: 'Transmis au régulateur',
            value: `${formatDateTime(reco.regulator_transmitted_at)}${author}`,
        });
    }

    return items;
});

const contentBlocks = computed(() => {
    const reco = recommendation.value;
    if (!reco) return [];

    return [
        { label: 'Thème', value: reco.theme },
        { label: 'Risque', value: reco.risk_type },
        { label: 'Recommandation', value: reco.recommendation_label },
        { label: 'Commentaires', value: reco.comments },
    ]
        .filter((block) => String(block.value ?? '').trim() !== '')
        .map((block) => ({
            ...block,
            wide: String(block.value).length > 80 || String(block.value).includes('\n'),
        }));
});

const closableStatuses = ['traitee', 'transmis'];

const showCloseButton = computed(() => {
    if (recommendation.value?.can_close) return true;

    const profile = auth.user?.profile;
    const status = recommendation.value?.status;

    return ['controle', 'audit', 'super_admin', 'admin'].includes(profile) && closableStatuses.includes(status);
});

const showTransmitButton = computed(() => {
    if (transmitFormOpen.value) return false;
    if (recommendation.value?.can_transmit_regulator) return true;

    const profile = auth.user?.profile;
    const status = recommendation.value?.status;

    return ['controle', 'audit', 'super_admin', 'admin'].includes(profile)
        && closableStatuses.includes(status);
});

const regulatorAvisComments = computed(() => (
    recommendation.value?.regulator_avis_comments
    ?? (recommendation.value?.regulator_comments ?? []).filter(
        (item) => (item.comment_type ?? 'avis') === 'avis',
    )
));

const showTransmissionComments = computed(() => {
    const transmissions = recommendation.value?.regulator_transmission_comments ?? [];
    return transmissions.length > 0 && showRegulatorComments.value;
});

const showRegulatorComments = computed(() => {
    if (recommendation.value?.can_view_regulator_comments) return true;

    const profile = auth.user?.profile;

    return ['controle', 'audit', 'super_admin', 'admin'].includes(profile)
        && recommendation.value?.regulator_transmitted_at;
});

const closeConfirmMessage = computed(() => {
    const comments = regulatorAvisComments.value;

    if (recommendation.value?.regulator_transmitted_at && comments.length) {
        return 'Clôturer cette recommandation et les actions associées ? L\'avis du régulateur a été pris en compte.';
    }

    if (recommendation.value?.regulator_transmitted_at) {
        return 'Clôturer cette recommandation sans avis du régulateur pour le moment ?';
    }

    return 'Clôturer cette recommandation et les actions associées ?';
});

function displayValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    return value;
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return formatDate(value);
    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function fileName(path) {
    if (!path) return '—';
    return String(path).split('/').pop();
}

async function loadRecommendation() {
    if (!props.recoId) return;

    loading.value = true;
    loadError.value = '';
    error.value = '';

    try {
        const { data } = await api.get(`/recommendations/${props.recoId}`);
        recommendation.value = data?.data ?? data;
    } catch {
        loadError.value = 'Impossible de charger le détail de la recommandation.';
        recommendation.value = null;
    } finally {
        loading.value = false;
    }
}

async function closeRecommendation() {
    if (!recommendation.value?.id || !showCloseButton.value) return;

    if (!window.confirm(closeConfirmMessage.value)) return;

    closing.value = true;
    error.value = '';

    try {
        const { data } = await api.post(`/recommendations/${recommendation.value.id}/close`);
        recommendation.value = data?.data ?? data;
        emit('closed', recommendation.value);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Clôture impossible.';
    } finally {
        closing.value = false;
    }
}

async function onTransmitted(payload) {
    recommendation.value = payload;
    transmitFormOpen.value = false;
    emit('transmitted', payload);
}

function openTransmitForm() {
    if (!showTransmitButton.value) return;
    transmitFormOpen.value = true;
    error.value = '';
}

watch(() => [props.open, props.recoId], ([isOpen]) => {
    if (isOpen) {
        loadRecommendation();
    } else {
        recommendation.value = null;
        loadError.value = '';
        error.value = '';
        transmitFormOpen.value = false;
    }
}, { immediate: true });
</script>
