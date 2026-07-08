<template>
    <div class="space-y-3">
        <div v-if="existingAttachments.length" class="space-y-2">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fichiers enregistrés</h4>
            <ul class="space-y-2">
                <li
                    v-for="path in existingAttachments"
                    :key="path"
                    class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
                >
                    <span class="truncate text-slate-800">{{ fileName(path) }}</span>
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            type="button"
                            class="text-xs font-medium text-emerald-700 hover:text-emerald-900 disabled:opacity-60"
                            :disabled="downloadingPath === path"
                            @click="download(path)"
                        >
                            {{ downloadingPath === path ? 'Téléchargement…' : 'Télécharger' }}
                        </button>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="text-xs text-red-600 hover:text-red-800"
                            @click="markForRemoval(path)"
                        >
                            Retirer
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <div v-if="canEdit" class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {{ existingAttachments.length ? 'Ajouter des pièces' : 'Pièces justificatives' }}
            </h4>
            <div
                v-for="(slot, index) in attachmentSlots"
                :key="slot.key"
                class="flex items-center gap-2"
            >
                <input type="file" class="flex-1 text-sm" @change="onFileSelected(index, $event)" />
                <button
                    v-if="attachmentSlots.length > 1"
                    type="button"
                    class="text-xs text-slate-500 hover:text-red-600"
                    @click="removeSlot(index)"
                >
                    ×
                </button>
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

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { attachmentFileName, downloadAttachment } from '../../utils/attachments';

const props = defineProps({
    planId: { type: Number, required: true },
    attachmentPaths: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
});

const emit = defineEmits(['saved']);

const saving = ref(false);
const error = ref('');
const downloadingPath = ref('');
const pendingRemoval = ref([]);
const attachmentSlots = ref([{ key: 1, file: null }]);
let slotKey = 1;

const existingAttachments = computed(() => (
    (props.attachmentPaths ?? []).filter((path) => !pendingRemoval.value.includes(path))
));

const hasPendingChanges = computed(() => (
    pendingRemoval.value.length > 0
    || attachmentSlots.value.some((slot) => slot.file)
));

function fileName(path) {
    return attachmentFileName(path);
}

async function download(path) {
    downloadingPath.value = path;
    error.value = '';

    try {
        await downloadAttachment(path);
    } catch {
        error.value = 'Téléchargement impossible.';
    } finally {
        downloadingPath.value = '';
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

function markForRemoval(path) {
    if (!pendingRemoval.value.includes(path)) {
        pendingRemoval.value.push(path);
    }
}

async function save() {
    if (!props.planId || !props.canEdit) return;

    saving.value = true;
    error.value = '';

    const fd = new FormData();
    pendingRemoval.value.forEach((path) => fd.append('remove_attachments[]', path));
    attachmentSlots.value
        .map((slot) => slot.file)
        .filter(Boolean)
        .forEach((file) => fd.append('attachments[]', file));

    try {
        const { data } = await api.post(
            `/recommendations/action-plans/${props.planId}/attachments`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } },
        );
        pendingRemoval.value = [];
        attachmentSlots.value = [{ key: 1, file: null }];
        slotKey = 1;
        emit('saved', data?.data ?? data);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

watch(() => props.planId, () => {
    pendingRemoval.value = [];
    attachmentSlots.value = [{ key: 1, file: null }];
    error.value = '';
});
</script>
