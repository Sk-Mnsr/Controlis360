<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="close"
    >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
                    <p v-if="mode === 'view'" class="mt-1 text-sm text-slate-500">Détail de la ligne du plan d'action</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-700" @click="close">×</button>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="save">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">N°</label>
                        <input
                            v-model.number="form.line_number"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="readonly"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Responsable</label>
                        <input
                            v-model="form.responsible_name"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="readonly"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Échéance</label>
                        <input
                            v-model="form.due_date"
                            type="date"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="readonly"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">En attente</label>
                        <label class="flex h-[42px] items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm">
                            <input
                                v-model="form.is_waiting"
                                type="checkbox"
                                class="rounded border-slate-300"
                                :disabled="readonly"
                            />
                            Oui
                        </label>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Statut</label>
                        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">{{ statusLabel }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date transmission</label>
                        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">{{ formatDate(form.transmission_date) }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Retard (j)</label>
                        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">{{ form.delay_days ?? '—' }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Plan d'action</label>
                    <textarea
                        v-model="form.action_plan"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        :readonly="readonly"
                    />
                </div>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

                <div class="flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="close"
                    >
                        Fermer
                    </button>
                    <button
                        v-if="!readonly"
                        type="submit"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                        :disabled="saving"
                    >
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { ACTION_PLAN_STATUSES } from '../../config/mission-parametrage';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    open: { type: Boolean, default: false },
    mode: { type: String, default: 'view' },
    plan: { type: Object, default: null },
    recoId: { type: Number, required: true },
    defaultResponsibleName: { type: String, default: '' },
    nextLineNumber: { type: Number, default: 1 },
});

const emit = defineEmits(['close', 'saved']);

const auth = useAuthStore();
const saving = ref(false);
const error = ref('');
const form = ref(emptyForm());

const readonly = computed(() => props.mode === 'view');
const title = computed(() => ({
    view: 'Voir le plan d\'action',
    edit: 'Modifier le plan d\'action',
    create: 'Ajouter un plan d\'action',
}[props.mode] ?? 'Plan d\'action'));

const statusLabel = computed(() => {
    if (props.plan?.status_fr) return props.plan.status_fr;
    if (form.value.is_waiting) return 'En attente';
    if (!form.value.action_plan?.trim()) return 'Non démarré';
    return ACTION_PLAN_STATUSES.find((item) => item.value === 'en_cours')?.label ?? 'En cours';
});

function emptyForm() {
    return {
        line_number: 1,
        action_plan: '',
        responsible_name: '',
        due_date: '',
        is_waiting: false,
        transmission_date: null,
        delay_days: null,
    };
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function syncForm() {
    if (props.mode === 'create') {
        form.value = {
            ...emptyForm(),
            line_number: props.nextLineNumber,
            responsible_name: props.defaultResponsibleName || auth.user?.name || '',
        };
        return;
    }

    if (!props.plan) {
        form.value = emptyForm();
        return;
    }

    form.value = {
        line_number: props.plan.line_number,
        action_plan: props.plan.action_plan ?? '',
        responsible_name: props.plan.responsible_name ?? '',
        due_date: props.plan.due_date ?? '',
        is_waiting: Boolean(props.plan.is_waiting),
        transmission_date: props.plan.transmission_date ?? null,
        delay_days: props.plan.delay_days ?? null,
    };
}

function close() {
    error.value = '';
    emit('close');
}

async function save() {
    if (readonly.value) return;

    if (!form.value.action_plan?.trim()) {
        error.value = 'Le plan d\'action est obligatoire.';
        return;
    }

    saving.value = true;
    error.value = '';

    const payload = {
        line_number: form.value.line_number,
        action_plan: form.value.action_plan.trim(),
        responsible_name: form.value.responsible_name?.trim() || props.defaultResponsibleName || auth.user?.name || '',
        due_date: form.value.due_date || null,
        is_waiting: Boolean(form.value.is_waiting),
    };

    try {
        if (props.mode === 'create') {
            await api.post(`/recommendations/${props.recoId}/action-plan`, payload);
        } else {
            await api.put(`/recommendations/action-plans/${props.plan.id}`, payload);
        }

        emit('saved');
        close();
    } catch (err) {
        error.value = err.response?.data?.message?.[0]
            ?? err.response?.data?.action_plan?.[0]
            ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

watch(() => [props.open, props.mode, props.plan], () => {
    if (props.open) {
        syncForm();
        error.value = '';
    }
}, { immediate: true });
</script>
