<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold">Formulaire d'action</h3>
            <p class="mt-1 text-sm text-slate-500">{{ mission?.reference }} — {{ mission?.title }}</p>

            <form class="mt-6 space-y-4" @submit.prevent="save">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">OWNERS</label>
                        <input
                            v-model="form.responsible_name"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="!canEdit"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Taux d'avancement (%)</label>
                        <input
                            v-model.number="form.progress_rate"
                            type="number"
                            min="0"
                            max="100"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="!canEdit"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date de début</label>
                        <input
                            v-model="form.action_start_date"
                            type="date"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="!canEdit"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date prévue de fin</label>
                        <input
                            v-model="form.planned_end_date"
                            type="date"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="!canEdit"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Montant de l'investissement</label>
                        <input
                            v-model="form.investment_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :readonly="!canEdit"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Go or No Go</label>
                        <select
                            v-model="form.go_no_go"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :disabled="!canEdit"
                        >
                            <option :value="null">Sélectionner</option>
                            <option value="go">Go</option>
                            <option value="no_go">No Go</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.needs_infrastructure_change"
                        type="checkbox"
                        class="rounded border-slate-300"
                        :disabled="!canEdit"
                    />
                    Nécessite un développement ou changement d'infrastructure
                </label>

                <div>
                    <label class="mb-1 block text-sm font-medium">Plan d'action</label>
                    <textarea
                        v-model="form.action_plan"
                        rows="3"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        :readonly="!canEdit"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Commentaire</label>
                    <textarea
                        v-model="form.comment"
                        rows="3"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        :readonly="!canEdit"
                    />
                </div>

                <div v-if="canEdit">
                    <label class="mb-1 block text-sm font-medium">Pièces justificatives</label>
                    <div class="space-y-2">
                        <div v-for="(slot, index) in attachmentSlots" :key="slot.key" class="flex gap-2">
                            <input type="file" class="flex-1 text-sm" @change="onFileSelected(index, $event)" />
                            <button
                                v-if="attachmentSlots.length > 1"
                                type="button"
                                class="text-xs text-slate-500"
                                @click="removeSlot(index)"
                            >
                                Retirer
                            </button>
                        </div>
                        <button type="button" class="text-sm text-emerald-700" @click="addSlot">+ Ajouter</button>
                    </div>
                </div>

                <ul v-if="response?.attachment_paths?.length" class="text-sm text-slate-600">
                    <li v-for="path in response.attachment_paths" :key="path">{{ fileName(path) }}</li>
                </ul>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4">
                    <button
                        v-if="response?.can_cancel"
                        type="button"
                        class="rounded-lg border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                        :disabled="saving"
                        @click="cancelDraft"
                    >
                        Annuler le brouillon
                    </button>
                    <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="$emit('close')">
                        Fermer
                    </button>
                    <button
                        v-if="canEdit"
                        type="submit"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                        :disabled="saving"
                    >
                        {{ saving ? 'Enregistrement...' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    open: { type: Boolean, default: false },
    mission: { type: Object, default: null },
    response: { type: Object, default: null },
});

const emit = defineEmits(['close', 'updated', 'cancelled']);

const auth = useAuthStore();
const saving = ref(false);
const error = ref('');
const attachmentSlots = ref([{ key: 1, file: null }]);
let slotKey = 1;

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

function fileName(path) {
    return path?.split('/').pop() ?? path;
}

function syncForm() {
    const r = props.response;
    form.responsible_name = r?.responsible_name ?? auth.user?.name ?? '';
    form.action_start_date = r?.action_start_date ?? '';
    form.planned_end_date = r?.planned_end_date ?? '';
    form.progress_rate = r?.progress_rate ?? null;
    form.action_plan = r?.action_plan ?? '';
    form.comment = r?.comment ?? '';
    form.needs_infrastructure_change = Boolean(r?.needs_infrastructure_change);
    form.investment_amount = r?.investment_amount ?? '';
    form.go_no_go = r?.go_no_go ?? null;
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

    attachmentSlots.value
        .map((s) => s.file)
        .filter(Boolean)
        .forEach((file) => fd.append('attachments[]', file));

    return fd;
}

async function updateResponse() {
    const { data } = await api.post(
        `/missions/${props.mission.id}/responses/${props.response.id}/update`,
        buildFormData(),
        { headers: { 'Content-Type': 'multipart/form-data' } },
    );

    return data?.data ?? data;
}

async function save() {
    if (!props.mission?.id || !props.response?.id) return;

    saving.value = true;
    error.value = '';

    try {
        const updated = await updateResponse();

        if (props.response?.can_submit_agent) {
            const { data } = await api.post(
                `/missions/${props.mission.id}/responses/${props.response.id}/submit-agent`,
            );
            emit('updated', data?.data ?? data);
            emit('close');
            return;
        }

        if (props.response?.can_forward) {
            const { data } = await api.post(
                `/missions/${props.mission.id}/responses/${props.response.id}/forward`,
            );
            emit('updated', data?.data ?? data);
            emit('close');
            return;
        }

        emit('updated', updated);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}

async function cancelDraft() {
    if (!props.mission?.id || !props.response?.id) return;
    if (!window.confirm('Annuler ce brouillon et revenir au choix Action / Passivité ?')) return;

    saving.value = true;
    error.value = '';

    try {
        await api.post(`/missions/${props.mission.id}/responses/${props.response.id}/cancel`);
        emit('cancelled');
        emit('close');
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Impossible d\'annuler le brouillon.';
    } finally {
        saving.value = false;
    }
}

function addSlot() {
    slotKey += 1;
    attachmentSlots.value.push({ key: slotKey, file: null });
}

function removeSlot(index) {
    attachmentSlots.value.splice(index, 1);
}

function onFileSelected(index, event) {
    attachmentSlots.value[index].file = event.target.files?.[0] ?? null;
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        error.value = '';
        attachmentSlots.value = [{ key: 1, file: null }];
        syncForm();
    }
});

watch(() => props.response, syncForm, { deep: true });
</script>
