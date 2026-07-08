<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold">Réponse — Passivité</h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ mission?.reference }} — transmis à l'initiateur de la mission
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium">Commentaire *</label>
                    <textarea
                        v-model="comment"
                        rows="4"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Motif de la passivité..."
                    />
                </div>

                <div>
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

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="$emit('close')">
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                        :disabled="saving"
                    >
                        {{ saving ? 'Envoi...' : 'Envoyer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../api/client';

const props = defineProps({
    open: { type: Boolean, default: false },
    mission: { type: Object, default: null },
});

const emit = defineEmits(['close', 'submitted']);

const comment = ref('');
const saving = ref(false);
const error = ref('');
const attachmentSlots = ref([{ key: 1, file: null }]);
let slotKey = 1;

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

async function submit() {
    if (!props.mission?.id || !comment.value.trim()) return;

    saving.value = true;
    error.value = '';

    const fd = new FormData();
    fd.append('passivity_comment', comment.value.trim());
    attachmentSlots.value
        .map((s) => s.file)
        .filter(Boolean)
        .forEach((file) => fd.append('attachments[]', file));

    try {
        const { data } = await api.post(`/missions/${props.mission.id}/responses/passivite`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        emit('submitted', data?.data ?? data);
        emit('close');
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Erreur lors de l\'envoi.';
    } finally {
        saving.value = false;
    }
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        comment.value = '';
        error.value = '';
        attachmentSlots.value = [{ key: 1, file: null }];
    }
});
</script>
