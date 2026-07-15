<template>
    <div>
        <div v-if="response && !canEdit" class="rounded-lg border border-emerald-200 bg-emerald-50/40 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-semibold text-slate-900">Réponse — Action</h3>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs text-emerald-800">
                    {{ response.workflow_status_fr }}
                </span>
            </div>
            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                <DetailRow label="OWNERS" :value="response.responsible_name" />
                <DetailRow label="Avancement" :value="progressLabel(response.progress_rate)" />
                <DetailRow label="Début" :value="formatDisplayDate(response.action_start_date)" />
                <DetailRow label="Fin prévue" :value="formatDisplayDate(response.planned_end_date)" />
                <DetailRow label="Go / No Go" :value="response.go_no_go_fr" />
                <DetailRow label="Investissement" :value="formatAmount(response.investment_amount)" />
                <DetailRow
                    label="Infrastructure"
                    :value="response.needs_infrastructure_change ? 'Oui' : 'Non'"
                />
                <DetailRow v-if="response.action_plan" label="Plan d'action" :value="response.action_plan" />
                <DetailRow v-if="response.comment" label="Commentaire" :value="response.comment" />
            </dl>
        </div>

        <form v-else class="space-y-4" @submit.prevent="save">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-semibold text-slate-900">Réponse — Action</h3>
                <span v-if="response?.workflow_status_fr" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">
                    {{ response.workflow_status_fr }}
                </span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">OWNERS</label>
                    <input
                        v-model="form.responsible_name"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Avancement (%)</label>
                    <input
                        v-model.number="form.progress_rate"
                        type="number"
                        min="0"
                        max="100"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Début</label>
                    <input
                        v-model="form.action_start_date"
                        type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Fin prévue</label>
                    <input
                        v-model="form.planned_end_date"
                        type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Investissement</label>
                    <input
                        v-model="form.investment_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Go / No Go</label>
                    <select v-model="form.go_no_go" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="null">Sélectionner</option>
                        <option value="go">Go</option>
                        <option value="no_go">No Go</option>
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input
                    v-model="form.needs_infrastructure_change"
                    type="checkbox"
                    class="rounded border-slate-300"
                />
                Infrastructure
            </label>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Plan d'action</label>
                <textarea
                    v-model="form.action_plan"
                    rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Commentaire</label>
                <textarea
                    v-model="form.comment"
                    rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                    :disabled="saving || !response"
                >
                    {{ saving ? 'Enregistrement…' : 'Save' }}
                </button>
            </div>
        </form>

        <p v-if="loading" class="text-sm text-slate-500">Chargement du formulaire d'action…</p>
        <p v-else-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import api from '../../api/client';
import DetailRow from './DetailRow.vue';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    mission: { type: Object, required: true },
    response: { type: Object, default: null },
});

const emit = defineEmits(['updated', 'ready']);

const auth = useAuthStore();
const saving = ref(false);
const loading = ref(false);
const loadError = ref('');
const error = ref('');

const form = reactive({
    responsible_name: '',
    action_start_date: '',
    planned_end_date: '',
    progress_rate: null,
    action_plan: '',
    comment: '',
    needs_infrastructure_change: false,
    investment_amount: '',
    go_no_go: null,
});

const canEdit = computed(() => props.response?.can_edit ?? false);

function formatDisplayDate(value) {
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

function syncForm() {
    const response = props.response;
    form.responsible_name = response?.responsible_name ?? auth.user?.name ?? '';
    form.action_start_date = response?.action_start_date ?? '';
    form.planned_end_date = response?.planned_end_date ?? '';
    form.progress_rate = response?.progress_rate ?? null;
    form.action_plan = response?.action_plan ?? '';
    form.comment = response?.comment ?? '';
    form.needs_infrastructure_change = Boolean(response?.needs_infrastructure_change);
    form.investment_amount = response?.investment_amount ?? '';
    form.go_no_go = response?.go_no_go ?? null;
}

function buildFormData() {
    const fd = new FormData();
    fd.append('responsible_name', form.responsible_name ?? '');
    if (form.action_start_date) fd.append('action_start_date', form.action_start_date);
    if (form.planned_end_date) fd.append('planned_end_date', form.planned_end_date);
    if (form.progress_rate !== null && form.progress_rate !== '') fd.append('progress_rate', form.progress_rate);
    fd.append('action_plan', form.action_plan ?? '');
    fd.append('comment', form.comment ?? '');
    fd.append('needs_infrastructure_change', form.needs_infrastructure_change ? '1' : '0');
    if (form.investment_amount !== '') fd.append('investment_amount', form.investment_amount);
    if (form.go_no_go) fd.append('go_no_go', form.go_no_go);

    return fd;
}

async function ensureActionResponse() {
    if (props.response?.id) {
        return props.response;
    }

    loading.value = true;
    loadError.value = '';

    try {
        const fd = new FormData();
        fd.append('handling_mode', 'self');
        const { data } = await api.post(`/missions/${props.mission.id}/responses/action/start`, fd);
        emit('updated', data?.data ?? data);
        return data?.data ?? data;
    } catch (err) {
        loadError.value = err.response?.data?.message?.[0] ?? 'Impossible d\'ouvrir le formulaire d\'action.';
        return null;
    } finally {
        loading.value = false;
    }
}

async function save() {
    let response = props.response;

    if (!response?.id) {
        response = await ensureActionResponse();
    }

    if (!response?.id) return;

    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.post(
            `/missions/${props.mission.id}/responses/${response.id}/update`,
            buildFormData(),
            { headers: { 'Content-Type': 'multipart/form-data' } },
        );

        const updated = data?.data ?? data;

        if (updated?.can_forward) {
            await api.post(`/missions/${props.mission.id}/responses/${updated.id}/forward`);
        } else if (updated?.can_submit_agent) {
            await api.post(`/missions/${props.mission.id}/responses/${updated.id}/submit-agent`);
        }

        emit('updated', updated);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}

watch(() => props.response, syncForm, { immediate: true, deep: true });

defineExpose({ ensureActionResponse });
</script>
