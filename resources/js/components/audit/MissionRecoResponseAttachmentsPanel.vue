<template>
    <div class="space-y-4">
        <p v-if="!response?.id" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Commencez par ouvrir <strong>Action</strong> et choisir un mode de traitement avant d'ajouter des pièces justificatives.
        </p>

        <template v-else>
            <div v-if="existingAttachments.length" class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-slate-700">
                            <th class="w-12 px-3 py-2 font-semibold">N°</th>
                            <th class="px-3 py-2 font-semibold">Nom du fichier</th>
                            <th class="px-3 py-2 font-semibold">Date de jointure</th>
                            <th class="px-3 py-2 font-semibold">Fichier</th>
                            <th class="px-3 py-2 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in existingAttachments"
                            :key="item.path"
                            class="border-t border-slate-200"
                        >
                            <td class="px-3 py-3 align-top font-medium text-slate-900">{{ item.line_number }}</td>
                            <td class="px-3 py-3 align-top text-slate-800">{{ item.name || '—' }}</td>
                            <td class="px-3 py-3 align-top text-slate-800">{{ formatDisplayDate(item.attached_at) }}</td>
                            <td class="px-3 py-3 align-top text-slate-600">{{ storageFileName(item.path) }}</td>
                            <td class="px-3 py-3 align-top">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        v-if="canPreview(item)"
                                        type="button"
                                        class="text-xs font-medium text-blue-700 hover:text-blue-900 disabled:opacity-60"
                                        :disabled="previewingPath === item.path"
                                        @click="preview(item)"
                                    >
                                        {{ previewingPath === item.path ? 'Ouverture…' : 'Visualiser' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-medium text-emerald-700 hover:text-emerald-900 disabled:opacity-60"
                                        :disabled="downloadingPath === item.path"
                                        @click="download(item)"
                                    >
                                        {{ downloadingPath === item.path ? 'Téléchargement…' : 'Télécharger' }}
                                    </button>
                                    <button
                                        v-if="canEdit"
                                        type="button"
                                        class="text-xs text-red-600 hover:text-red-800"
                                        @click="markForRemoval(item.path)"
                                    >
                                        Retirer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="canEdit" class="space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ existingAttachments.length ? 'Ajouter des pièces' : 'Pièces justificatives' }}
                </h4>

                <div
                    v-for="(slot, index) in attachmentSlots"
                    :key="slot.key"
                    class="rounded-lg border border-slate-200 bg-white p-4"
                >
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Pièce {{ existingAttachments.length + index + 1 }}
                        </span>
                        <button
                            v-if="attachmentSlots.length > 1"
                            type="button"
                            class="text-xs text-slate-500 hover:text-red-600"
                            @click="removeSlot(index)"
                        >
                            Retirer la ligne
                        </button>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Nom du fichier</label>
                            <input
                                v-model="slot.name"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                placeholder="Ex. Rapport de clôture"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date de jointure</label>
                            <input
                                v-model="slot.attached_at"
                                type="date"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Fichier</label>
                            <input type="file" class="w-full text-sm" @change="onFileSelected(index, $event)" />
                            <p v-if="slot.file" class="mt-1 text-xs text-slate-500">{{ slot.file.name }}</p>
                        </div>
                    </div>
                </div>

                <button type="button" class="text-sm text-emerald-700 hover:text-emerald-900" @click="addSlot">
                    + Ajouter une pièce jointe
                </button>

                <div class="flex justify-end">
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
        error.value = err.response?.data?.message?.[0]
            ?? err.response?.data?.attachment_names?.[0]?.[0]
            ?? 'Enregistrement impossible.';
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
