<template>
    <div class="space-y-4">
        <p v-if="!response?.id" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Commencez par ouvrir <strong>Action</strong> et choisir un mode de traitement avant d'ajouter des pièces justificatives.
        </p>

        <template v-else>
            <ul v-if="existingAttachments.length" class="divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <li
                    v-for="item in existingAttachments"
                    :key="item.path"
                    class="flex flex-wrap items-center gap-3 px-4 py-3"
                >
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-[10px] font-bold uppercase text-red-700">
                        {{ fileExtension(item) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ item.name || storageFileName(item.path) }}</p>
                        <p class="text-xs text-slate-500">{{ formatDisplayDate(item.attached_at) }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            v-if="canPreview(item)"
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-60"
                            :disabled="previewingPath === item.path"
                            @click="preview(item)"
                        >
                            {{ previewingPath === item.path ? '…' : 'Voir' }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-emerald-800 hover:bg-emerald-50 disabled:opacity-60"
                            :disabled="downloadingPath === item.path"
                            @click="download(item)"
                        >
                            {{ downloadingPath === item.path ? '…' : 'Télécharger' }}
                        </button>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                            @click="markForRemoval(item.path)"
                        >
                            Retirer
                        </button>
                    </div>
                </li>
            </ul>

            <p v-if="pendingRemoval.length" class="text-xs text-amber-800">
                {{ pendingRemoval.length }} pièce{{ pendingRemoval.length > 1 ? 's seront retirées' : ' sera retirée' }} à l'enregistrement.
            </p>

            <div v-if="canEdit" class="space-y-3">
                <div
                    v-for="(slot, index) in attachmentSlots"
                    :key="slot.key"
                    class="rounded-xl border border-slate-200 bg-white p-3"
                >
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Nouvelle pièce {{ index + 1 }}
                        </span>
                        <button
                            v-if="attachmentSlots.length > 1"
                            type="button"
                            class="text-xs text-slate-500 hover:text-red-600"
                            @click="removeSlot(index)"
                        >
                            Retirer
                        </button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1.4fr)_10rem_auto] sm:items-center">
                        <input
                            v-model="slot.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Nom de la pièce"
                            :aria-label="`Nom de la pièce ${index + 1}`"
                        />
                        <input
                            v-model="slot.attached_at"
                            type="date"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            :aria-label="`Date de la pièce ${index + 1}`"
                        />
                        <label class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                            <span class="max-w-[12rem] truncate">{{ slot.file?.name || 'Choisir un fichier' }}</span>
                            <input type="file" class="sr-only" @change="onFileSelected(index, $event)" />
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" class="text-sm font-medium text-emerald-800 hover:text-emerald-950" @click="addSlot">
                        + Ajouter une pièce
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                        :disabled="saving || !hasPendingChanges"
                        @click="save"
                    >
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </div>

            <p v-else-if="!existingAttachments.length" class="text-sm text-slate-500">
                Aucune pièce justificative jointe.
            </p>
        </template>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import {
    attachmentFileName,
    downloadAttachment,
    isPreviewableAttachment,
    previewAttachment,
} from '../../utils/attachments';

const props = defineProps({
    mission: { type: Object, required: true },
    response: { type: Object, default: null },
});

const emit = defineEmits(['updated']);

const saving = ref(false);
const error = ref('');
const downloadingPath = ref('');
const previewingPath = ref('');
const pendingRemoval = ref([]);
const attachmentSlots = ref([]);
let slotKey = 1;

const canEdit = computed(() => Boolean(props.response?.can_edit));

const existingAttachments = computed(() => {
    const items = props.response?.attachments?.length
        ? props.response.attachments
        : (props.response?.attachment_paths ?? []).map((path, index) => ({
            line_number: index + 1,
            path: typeof path === 'string' ? path : path?.path,
            name: typeof path === 'object' ? path?.name : attachmentFileName(path),
            attached_at: typeof path === 'object' ? path?.attached_at : null,
            can_preview: isPreviewableAttachment(path),
        }));

    return items.filter((item) => item.path && !pendingRemoval.value.includes(item.path));
});

const hasPendingChanges = computed(() => (
    pendingRemoval.value.length > 0
    || attachmentSlots.value.some((slot) => slot.file || slot.name?.trim())
));

function todayIso() {
    return new Date().toISOString().slice(0, 10);
}

function formatDisplayDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function storageFileName(path) {
    return attachmentFileName(path);
}

function fileExtension(item) {
    const name = item.name || storageFileName(item.path) || '';
    const extension = String(name).split('.').pop()?.slice(0, 4).toUpperCase();

    return extension && extension !== String(name).toUpperCase() ? extension : 'DOC';
}

function canPreview(item) {
    return item.can_preview ?? isPreviewableAttachment(item.path);
}

function createSlot() {
    slotKey += 1;

    return {
        key: slotKey,
        name: '',
        attached_at: todayIso(),
        file: null,
    };
}

function resetSlots() {
    attachmentSlots.value = [createSlot()];
}

async function download(item) {
    downloadingPath.value = item.path;
    error.value = '';

    try {
        await downloadAttachment(item.path, item.name || storageFileName(item.path));
    } catch {
        error.value = 'Téléchargement impossible.';
    } finally {
        downloadingPath.value = '';
    }
}

async function preview(item) {
    previewingPath.value = item.path;
    error.value = '';

    try {
        await previewAttachment(item.path);
    } catch {
        error.value = 'Visualisation impossible.';
    } finally {
        previewingPath.value = '';
    }
}

function addSlot() {
    attachmentSlots.value.push(createSlot());
}

function removeSlot(index) {
    attachmentSlots.value.splice(index, 1);
}

function onFileSelected(index, event) {
    const file = event.target.files?.[0] ?? null;
    attachmentSlots.value[index].file = file;

    if (file && !attachmentSlots.value[index].name?.trim()) {
        attachmentSlots.value[index].name = file.name.replace(/\.[^.]+$/, '');
    }
}

function attachmentErrorMessage(err) {
    const data = err.response?.data;

    if (data && typeof data === 'object') {
        const errors = data.errors;
        if (errors && typeof errors === 'object') {
            const text = Object.values(errors)
                .map((value) => (Array.isArray(value) ? value.join(' ') : String(value ?? '')))
                .join(' ')
                .replace(/<br\s*\/?>/gi, ' ')
                .trim();

            if (text) {
                return text;
            }
        }

        if (typeof data.message === 'string' && data.message.trim()) {
            return data.message;
        }

        if (Array.isArray(data.message) && data.message[0]) {
            return String(data.message[0]);
        }
    }

    if (err.response?.status === 413) {
        return 'Le fichier est trop volumineux pour le serveur.';
    }

    return 'Enregistrement impossible.';
}

function markForRemoval(path) {
    if (!pendingRemoval.value.includes(path)) {
        pendingRemoval.value.push(path);
    }
}

async function save() {
    if (!props.mission?.id || !props.response?.id || !canEdit.value) return;

    const slotsToUpload = attachmentSlots.value.filter((slot) => slot.file || slot.name?.trim());
    const invalidSlot = slotsToUpload.find((slot) => !slot.file || !slot.name?.trim());

    if (invalidSlot) {
        error.value = 'Chaque pièce doit avoir un nom, une date et un fichier.';
        return;
    }

    const oversized = slotsToUpload.find((slot) => slot.file.size > 50 * 1024 * 1024);
    if (oversized) {
        error.value = `« ${oversized.file.name} » dépasse 50 Mo.`;
        return;
    }

    if (!hasPendingChanges.value) {
        error.value = 'Ajoutez au moins une pièce ou retirez un fichier existant.';
        return;
    }

    saving.value = true;
    error.value = '';

    const fd = new FormData();
    pendingRemoval.value.forEach((path) => fd.append('remove_attachments[]', path));
    slotsToUpload.forEach((slot) => {
        fd.append('attachment_names[]', slot.name.trim());
        fd.append('attachment_dates[]', slot.attached_at || todayIso());
        fd.append('attachments[]', slot.file);
    });

    try {
        const { data } = await api.post(
            `/missions/${props.mission.id}/responses/${props.response.id}/attachments`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } },
        );
        pendingRemoval.value = [];
        resetSlots();
        emit('updated', data?.data ?? data);
    } catch (err) {
        error.value = attachmentErrorMessage(err);
    } finally {
        saving.value = false;
    }
}

watch(() => props.response?.id, () => {
    pendingRemoval.value = [];
    resetSlots();
    error.value = '';
}, { immediate: true });
</script>
