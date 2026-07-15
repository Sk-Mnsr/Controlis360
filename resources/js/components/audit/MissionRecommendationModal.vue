<template>
    <div
        v-if="open"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="shrink-0 border-b border-slate-200 px-6 py-5">
                <h3 class="text-lg font-bold text-slate-900">Nouvelle recommandation</h3>
                <p class="mt-1 text-sm text-slate-500">Mission {{ mission?.reference }}</p>
            </div>

            <form class="flex-1 overflow-y-auto p-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Référence</label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm"
                            :value="referencePreview"
                            readonly
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Thème</label>
                        <input
                            v-model="form.theme"
                            type="text"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Thème de la recommandation"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Recommandation</label>
                        <textarea
                            v-model="form.recommendation_label"
                            required
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Texte de la recommandation"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Type de risque</label>
                        <input
                            v-model="form.risk_type"
                            type="text"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Ex. Risque opérationnel, Risque de conformité..."
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Niveau de risque</label>
                        <select v-model="form.risk_level" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="" disabled>Sélectionner</option>
                            <option v-for="level in RISK_LEVELS" :key="level.value" :value="level.value">
                                {{ level.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Priorité</label>
                        <select v-model="form.priority" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="" disabled>Sélectionner</option>
                            <option v-for="priority in PRIORITIES" :key="priority.value" :value="priority.value">
                                {{ priority.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">OWNERS</label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm"
                            :value="responsiblePreview"
                            readonly
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Département</label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm"
                            :value="departmentNames"
                            readonly
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Date émission</label>
                        <input v-model="form.recommendation_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Date échéance</label>
                        <input v-model="form.due_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Statut</label>
                        <select v-model="form.status" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="status in RECOMMENDATION_STATUSES" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Commentaires</label>
                        <textarea
                            v-model="form.comments"
                            rows="2"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Observations"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Pièces jointes</label>
                        <div class="space-y-2">
                            <div v-for="(slot, index) in attachmentSlots" :key="slot.key" class="flex gap-2">
                                <input type="file" class="flex-1 text-sm" @change="onFileSelected(index, $event)" />
                                <button
                                    v-if="attachmentSlots.length > 1"
                                    type="button"
                                    class="text-sm text-slate-500 hover:text-red-600"
                                    @click="removeSlot(index)"
                                >
                                    Retirer
                                </button>
                            </div>
                            <button type="button" class="text-sm font-medium text-emerald-700" @click="addSlot">
                                + Ajouter une pièce jointe
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
            </form>

            <div class="flex shrink-0 justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700"
                    @click="$emit('close')"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                    :disabled="saving"
                    @click="submit"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer la reco' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import api from '../../api/client';
import { RECOMMENDATION_STATUSES, PRIORITIES, RISK_LEVELS } from '../../config/mission-parametrage';

const props = defineProps({
    open: { type: Boolean, default: false },
    mission: { type: Object, default: null },
});

const emit = defineEmits(['close', 'created']);

const saving = ref(false);
const error = ref('');
const attachmentSlots = ref([{ key: 1, file: null }]);
let slotKey = 1;

const form = reactive({
    theme: '',
    recommendation_label: '',
    risk_type: '',
    risk_level: '',
    priority: '',
    recommendation_date: '',
    due_date: '',
    status: 'emise',
    comments: '',
});

const referencePreview = computed(() => props.mission?.next_recommendation_reference ?? `${props.mission?.reference ?? ''}-R01`);

const departmentNames = computed(() => {
    const names = (props.mission?.entities ?? []).map((e) => e.name).filter(Boolean);
    return names.length ? names.join(', ') : '—';
});

const responsiblePreview = computed(() => props.mission?.responsible_preview ?? '—');

watch(() => props.open, (isOpen) => {
    if (!isOpen) return;
    form.theme = '';
    form.recommendation_label = '';
    form.risk_type = '';
    form.risk_level = '';
    form.priority = '';
    form.recommendation_date = new Date().toISOString().slice(0, 10);
    form.due_date = '';
    form.status = props.mission?.status ?? 'emise';
    form.comments = '';
    error.value = '';
    attachmentSlots.value = [{ key: 1, file: null }];
});

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

function buildFormData() {
    const fd = new FormData();
    fd.append('theme', form.theme.trim());
    fd.append('recommendation_label', form.recommendation_label);
    fd.append('risk_type', form.risk_type.trim());
    fd.append('risk_level', form.risk_level);
    fd.append('priority', form.priority);
    fd.append('recommendation_date', form.recommendation_date);
    if (form.due_date) fd.append('due_date', form.due_date);
    fd.append('status', form.status);
    if (form.comments) fd.append('comments', form.comments);
    attachmentSlots.value.map((s) => s.file).filter(Boolean).forEach((file) => fd.append('attachments[]', file));
    return fd;
}

async function submit() {
    if (!props.mission?.id) return;
    saving.value = true;
    error.value = '';
    try {
        const { data } = await api.post(`/missions/${props.mission.id}/recommendation`, buildFormData(), {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        emit('created', data?.data ?? data);
        emit('close');
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : err.response?.data?.message?.[0] ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}
</script>
