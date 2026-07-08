<template>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold uppercase tracking-wide text-emerald-900">Transmission au régulateur</h4>
                <p class="mt-1 text-xs text-emerald-800">Un commentaire est obligatoire pour chaque transmission.</p>
            </div>
            <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-600 hover:bg-white"
                @click="$emit('cancel')"
            >
                Annuler
            </button>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date</label>
                <input
                    v-model="commentedAt"
                    type="date"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Auteur</label>
                <input
                    :value="authorName"
                    type="text"
                    readonly
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800"
                />
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Commentaire</label>
                <textarea
                    v-model="comment"
                    rows="4"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Précisez le contexte transmis au régulateur…"
                />
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button
                type="button"
                class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                :disabled="saving"
                @click="submit"
            >
                {{ saving ? 'Transmission…' : 'Transmettre' }}
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    recoId: { type: Number, required: true },
});

const emit = defineEmits(['transmitted', 'cancel']);

const auth = useAuthStore();

const authorName = computed(() => auth.user?.name ?? '—');
const saving = ref(false);
const error = ref('');
const commentedAt = ref('');
const comment = ref('');

function todayIso() {
    return new Date().toISOString().slice(0, 10);
}

function resetForm() {
    commentedAt.value = todayIso();
    comment.value = '';
    error.value = '';
}

async function submit() {
    const trimmed = comment.value?.trim() ?? '';
    if (!trimmed) {
        error.value = 'Le commentaire de transmission est obligatoire.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.post(`/recommendations/${props.recoId}/transmit-regulator`, {
            entries: [{
                commented_at: commentedAt.value || todayIso(),
                comment: trimmed,
                author_name: authorName.value !== '—' ? authorName.value : '',
            }],
        });
        resetForm();
        emit('transmitted', data?.data ?? data);
    } catch (err) {
        error.value = err.response?.data?.message?.[0]
            ?? err.response?.data?.entries?.[0]?.[0]
            ?? 'Transmission impossible.';
    } finally {
        saving.value = false;
    }
}

watch(() => props.recoId, resetForm, { immediate: true });
</script>
