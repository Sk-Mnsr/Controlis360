<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-slate-800">Pièces jointes</h3>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xl font-light leading-none text-sky-600 hover:bg-sky-50 hover:text-sky-800"
                            title="Ajouter un champ de jointure"
                            @click="addSlot"
                        >
                            +
                        </button>
                    </div>
                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        {{ activityTitle || 'Sans titre' }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded px-2 text-lg leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    @click="$emit('close')"
                >
                    ×
                </button>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto px-4 py-3">
                <p v-if="error" class="text-xs text-red-600">{{ error }}</p>

                <div v-if="!attachments.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-6 text-center text-xs text-slate-400">
                    Aucune pièce jointe.
                </div>

                <ul v-else class="space-y-2">
                    <li
                        v-for="item in attachments"
                        :key="item.path"
                        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium text-slate-800" :title="item.name">
                                {{ item.name }}
                            </p>
                            <p v-if="item.attached_at" class="text-[10px] text-slate-400">
                                {{ formatDateTime(item.attached_at) }}
                            </p>
                        </div>
                        <button
                            v-if="item.can_preview"
                            type="button"
                            class="rounded border border-sky-300 px-2 py-1 text-[10px] font-semibold text-sky-700 hover:bg-sky-50 disabled:opacity-50"
                            :disabled="busyPath === item.path"
                            @click="preview(item)"
                        >
                            Voir
                        </button>
                        <button
                            type="button"
                            class="rounded border border-slate-300 px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-white disabled:opacity-50"
                            :disabled="busyPath === item.path"
                            @click="download(item)"
                        >
                            Télécharger
                        </button>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="rounded border border-red-200 px-2 py-1 text-[10px] font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50"
                            :disabled="saving || busyPath === item.path"
                            title="Supprimer"
                            @click="remove(item)"
                        >
                            ×
                        </button>
                    </li>
                </ul>

                <div v-if="canEdit" class="space-y-2">
                    <p class="text-xs font-semibold text-slate-700">Ajouter des fichiers</p>
                    <div
                        v-for="(slot, index) in slots"
                        :key="slot.key"
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                Jointure {{ index + 1 }}
                            </span>
                            <button
                                v-if="slots.length > 1"
                                type="button"
                                class="text-[10px] font-medium text-red-600 hover:text-red-800"
                                :disabled="saving"
                                @click="removeSlot(index)"
                            >
                                Retirer
                            </button>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                    Nom
                                </label>
                                <input
                                    v-model="slot.name"
                                    type="text"
                                    class="w-full rounded border border-slate-300 px-2 py-1.5 text-xs outline-none focus:border-sky-400"
                                    placeholder="Nom de la pièce jointe"
                                    :disabled="saving"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                    Pièce jointe
                                </label>
                                <input
                                    type="file"
                                    class="block w-full text-xs text-slate-600 file:mr-2 file:rounded file:border-0 file:bg-sky-50 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-sky-700 hover:file:bg-sky-100"
                                    :disabled="saving"
                                    @change="onSlotFileChange(index, $event)"
                                />
                            </div>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">
                        Taille max. 10 Mo par fichier — cliquez sur + pour ajouter d’autres jointures
                    </p>
                    <button
                        type="button"
                        class="rounded-lg bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800 disabled:opacity-50"
                        :disabled="saving || !hasPendingFiles"
                        @click="uploadPending"
                    >
                        {{ saving ? 'Envoi…' : `Joindre${pendingCount ? ` (${pendingCount})` : ''}` }}
                    </button>
                </div>
            </div>

            <div class="flex shrink-0 justify-end border-t border-slate-200 px-4 py-3">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    @click="$emit('close')"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { downloadAttachment, previewAttachment } from '../../utils/attachments';

const props = defineProps({
    open: { type: Boolean, default: false },
    activityId: { type: [Number, String], default: null },
    activityTitle: { type: String, default: '' },
    initialAttachments: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'updated']);

const attachments = ref([]);
const slots = ref([]);
const saving = ref(false);
const busyPath = ref('');
const error = ref('');
let slotKey = 0;

const pendingSlots = computed(() => slots.value.filter((slot) => slot.file));
const pendingCount = computed(() => pendingSlots.value.length);
const hasPendingFiles = computed(() => pendingCount.value > 0);

function createSlot() {
    slotKey += 1;
    return { key: slotKey, name: '', file: null };
}

function addSlot() {
    slots.value.push(createSlot());
}

function removeSlot(index) {
    if (slots.value.length <= 1) return;
    slots.value.splice(index, 1);
}

function onSlotFileChange(index, event) {
    const file = event.target.files?.[0] ?? null;
    if (!slots.value[index]) return;
    slots.value[index].file = file;
    if (file && !String(slots.value[index].name || '').trim()) {
        slots.value[index].name = file.name.replace(/\.[^.]+$/, '') || file.name;
    }
}

function formatDateTime(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function syncFromProps() {
    attachments.value = [...(props.initialAttachments || [])];
    slots.value = [createSlot()];
    error.value = '';
    busyPath.value = '';
}

async function preview(item) {
    busyPath.value = item.path;
    error.value = '';
    try {
        await previewAttachment(item);
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible de visualiser le fichier.';
    } finally {
        busyPath.value = '';
    }
}

async function download(item) {
    busyPath.value = item.path;
    error.value = '';
    try {
        await downloadAttachment(item, item.name);
    } catch (err) {
        error.value = err.response?.data?.message || 'Impossible de télécharger le fichier.';
    } finally {
        busyPath.value = '';
    }
}

async function remove(item) {
    if (!props.activityId || !props.canEdit) return;
    const confirmed = window.confirm(`Supprimer « ${item.name} » ?`);
    if (!confirmed) return;

    saving.value = true;
    error.value = '';
    try {
        const fd = new FormData();
        fd.append('remove_attachments[]', item.path);
        const { data } = await api.post(`/gouvernance-it/activities/${props.activityId}/attachments`, fd);
        const payload = data.data ?? data;
        attachments.value = payload.attachments ?? [];
        emit('updated', payload);
    } catch (err) {
        error.value = err.response?.data?.message
            || Object.values(err.response?.data?.errors ?? {}).flat().join(' ')
            || 'Impossible de supprimer le fichier.';
    } finally {
        saving.value = false;
    }
}

async function uploadPending() {
    if (!props.activityId || !props.canEdit || !hasPendingFiles.value) return;

    const ready = pendingSlots.value;
    if (!ready.length) return;

    saving.value = true;
    error.value = '';
    try {
        const fd = new FormData();
        ready.forEach((slot) => {
            fd.append('attachments[]', slot.file);
            fd.append('attachment_names[]', String(slot.name || '').trim() || slot.file.name);
        });
        const { data } = await api.post(`/gouvernance-it/activities/${props.activityId}/attachments`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        const payload = data.data ?? data;
        attachments.value = payload.attachments ?? [];
        emit('updated', payload);
        slots.value = [createSlot()];
    } catch (err) {
        error.value = err.response?.data?.message
            || Object.values(err.response?.data?.errors ?? {}).flat().join(' ')
            || 'Impossible d’ajouter les fichiers.';
    } finally {
        saving.value = false;
    }
}

watch(
    () => [props.open, props.activityId, props.initialAttachments],
    ([isOpen]) => {
        if (isOpen) {
            syncFromProps();
        }
    },
    { deep: true },
);
</script>
