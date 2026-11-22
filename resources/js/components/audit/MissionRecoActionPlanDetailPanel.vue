<template>
    <div
        v-if="open && plan"
        class="fixed inset-0 z-[55] flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Plan d'action</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-900">Ligne {{ plan.line_number }}</h3>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="statusClasses"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
                            {{ plan.status_fr || '—' }}
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <button
                        v-if="canEdit"
                        type="button"
                        class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-800 hover:bg-emerald-50"
                        @click="$emit('edit', plan)"
                    >
                        Modifier
                    </button>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                        @click="$emit('delete', plan)"
                    >
                        Supprimer
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                        :class="section === 'attachments'
                            ? 'border-slate-800 bg-slate-800 text-white'
                            : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
                        @click="toggleSection('attachments')"
                    >
                        Pièces
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                        :class="section === 'replies'
                            ? 'border-emerald-700 bg-emerald-700 text-white'
                            : 'border-emerald-300 text-emerald-800 hover:bg-emerald-50'"
                        @click="toggleSection('replies')"
                    >
                        Réponses
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                        title="Fermer"
                        @click="$emit('close')"
                    >
                        ×
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <article class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                    <h4 class="text-xs font-medium uppercase tracking-wide text-slate-500">Plan d'action</h4>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-slate-900">{{ plan.action_plan || '—' }}</p>
                </article>

                <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Responsable</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ plan.responsible_name || '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Échéance</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(plan.due_date) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">En attente</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ plan.is_waiting ? 'Oui' : 'Non' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Transmission</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(plan.transmission_date) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Retard</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ formatDelay(plan.delay_days) }}</dd>
                    </div>
                </dl>

                <div v-if="section === 'attachments'" class="mt-5 rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Pièces justificatives
                    </h4>
                    <MissionRecoActionPlanAttachments
                        :plan-id="plan.id"
                        :attachment-paths="plan.attachment_paths ?? []"
                        :can-edit="canEditAttachments"
                        @saved="onAttachmentsSaved"
                    />
                </div>

                <div v-if="section === 'replies'" class="mt-5 rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Réponses aux actions
                    </h4>
                    <MissionRecoActionPlanComments
                        :plan-id="plan.id"
                        :comments="plan.comments ?? []"
                        :can-comment="canComment"
                        @saved="$emit('updated')"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { actionPlanStatusClasses } from '../../config/mission-parametrage';
import MissionRecoActionPlanAttachments from './MissionRecoActionPlanAttachments.vue';
import MissionRecoActionPlanComments from './MissionRecoActionPlanComments.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    plan: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
    canEditAttachments: { type: Boolean, default: false },
    canComment: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'view', 'edit', 'delete', 'updated']);

const section = ref(null);

const statusClasses = computed(() => actionPlanStatusClasses(props.plan?.status_color));

const dotClass = computed(() => ({
    blue: 'bg-blue-500',
    amber: 'bg-amber-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
    emerald: 'bg-emerald-500',
    slate: 'bg-slate-500',
}[props.plan?.status_color] ?? 'bg-slate-400'));

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function formatDelay(value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${value} j`;
}

function toggleSection(name) {
    section.value = section.value === name ? null : name;
}

function onAttachmentsSaved(plan) {
    emit('updated', plan);
}

watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        section.value = null;
    }
});
</script>
