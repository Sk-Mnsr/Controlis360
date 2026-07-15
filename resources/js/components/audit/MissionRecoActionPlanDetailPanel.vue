<template>
    <div
        v-if="open && plan"
        class="fixed inset-0 z-[55] flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Plan d'action</p>
                    <h3 class="text-lg font-bold text-slate-900">Ligne {{ plan.line_number }}</h3>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <button
                        type="button"
                        class="rounded border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                        @click="$emit('view', plan)"
                    >
                        Voir
                    </button>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="rounded border border-emerald-300 px-3 py-1.5 text-xs text-emerald-800 hover:bg-emerald-50"
                        @click="$emit('edit', plan)"
                    >
                        Modifier
                    </button>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="rounded border border-red-200 px-3 py-1.5 text-xs text-red-700 hover:bg-red-50"
                        @click="$emit('delete', plan)"
                    >
                        Supprimer
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-sm transition"
                        :class="section === 'attachments'
                            ? 'border-slate-700 bg-slate-700 text-white'
                            : 'border-slate-300 bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        title="Pièces justificatives"
                        @click="toggleSection('attachments')"
                    >
                        📎
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-sm font-semibold transition"
                        :class="section === 'replies'
                            ? 'border-emerald-700 bg-emerald-700 text-white'
                            : 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
                        title="Réponses aux actions"
                        @click="toggleSection('replies')"
                    >
                        +
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
                <div class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-emerald-800">Résumé</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li>
                            <span class="text-slate-600">N° :</span>
                            <span class="font-bold text-slate-900">{{ plan.line_number }}</span>
                        </li>
                        <li>
                            <span class="text-slate-600">Responsable :</span>
                            <span class="font-bold text-slate-900">{{ plan.responsible_name || '—' }}</span>
                        </li>
                        <li>
                            <span class="text-slate-600">Échéance :</span>
                            <span class="font-bold text-slate-900">{{ formatDate(plan.due_date) }}</span>
                        </li>
                        <li class="flex flex-wrap items-center gap-2">
                            <span class="text-slate-600">Statut :</span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusClasses"
                            >
                                <span class="h-2 w-2 rounded-full" :class="dotClass" />
                                {{ plan.status_fr }}
                            </span>
                        </li>
                        <li>
                            <span class="text-slate-600">Date transmission :</span>
                            <span class="font-bold text-slate-900">{{ formatDate(plan.transmission_date) }}</span>
                        </li>
                        <li>
                            <span class="text-slate-600">Retard (j) :</span>
                            <span class="font-bold text-slate-900">{{ formatDelay(plan.delay_days) }}</span>
                        </li>
                        <li>
                            <span class="text-slate-600">En attente :</span>
                            <span class="font-bold text-slate-900">{{ plan.is_waiting ? 'Oui' : '—' }}</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                    <h4 class="text-sm font-semibold text-slate-700">Plan d'action</h4>
                    <p class="mt-2 whitespace-pre-wrap text-sm font-bold text-slate-900">{{ plan.action_plan || '—' }}</p>
                </div>

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
    if (value === null || value === undefined) return '—';
    return value;
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
