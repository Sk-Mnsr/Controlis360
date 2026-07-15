<template>
    <div class="space-y-2">
        <div
            v-for="item in sortedComments"
            :key="item.id"
            class="rounded-lg border border-slate-200 bg-white p-3"
        >
            <p class="text-xs font-medium text-slate-500">
                {{ item.author_name || 'Utilisateur' }}
                <span class="font-normal">— {{ formatDate(item.commented_at) }}</span>
            </p>
            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ item.comment }}</p>
        </div>

        <template v-if="canComment">
            <div v-if="showForm" class="space-y-2 rounded-lg border border-emerald-200 bg-emerald-50/40 p-3">
                <div
                    v-for="(row, index) in rows"
                    :key="row.key"
                    class="space-y-2"
                >
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Date</label>
                            <input
                                v-model="row.commented_at"
                                type="date"
                                class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Auteur</label>
                            <input
                                :value="authorName"
                                type="text"
                                readonly
                                class="w-full rounded border border-slate-200 bg-slate-50 px-2 py-1.5 text-sm text-slate-700"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Réponse</label>
                        <textarea
                            v-model="row.comment"
                            rows="2"
                            class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                            placeholder="Répondre à cette action…"
                        />
                    </div>
                    <button
                        v-if="rows.length > 1"
                        type="button"
                        class="text-xs text-slate-500 hover:text-red-600"
                        @click="removeRow(index)"
                    >
                        Retirer
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 text-lg font-semibold text-emerald-800 hover:bg-emerald-100"
                        title="Ajouter une ligne"
                        @click="addRow"
                    >
                        +
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                        :disabled="saving"
                        @click="save"
                    >
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
                        @click="cancelForm"
                    >
                        Annuler
                    </button>
                </div>
            </div>
            <button
                v-else
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 text-lg font-semibold text-emerald-800 hover:bg-emerald-100"
                title="Répondre"
                @click="openForm"
            >
                +
            </button>
        </template>

        <p v-if="!sortedComments.length && !canComment" class="text-sm text-slate-400">Aucune réponse.</p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    planId: { type: Number, required: true },
    comments: { type: Array, default: () => [] },
    canComment: { type: Boolean, default: true },
});

const emit = defineEmits(['saved']);

const auth = useAuthStore();
const authorName = computed(() => auth.user?.name ?? '—');

const sortedComments = computed(() => [...(props.comments ?? [])].sort((a, b) => {
    const dateCompare = String(b.commented_at ?? '').localeCompare(String(a.commented_at ?? ''));
    if (dateCompare !== 0) return dateCompare;

    return Number(b.id ?? 0) - Number(a.id ?? 0);
}));

const showForm = ref(false);
const saving = ref(false);
const error = ref('');
const rows = ref([]);
let rowKey = 1;

function todayIso() {
    return new Date().toISOString().slice(0, 10);
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

function createRow() {
    rowKey += 1;

    return {
        key: rowKey,
        commented_at: todayIso(),
        comment: '',
    };
}

function resetRows() {
    rows.value = [createRow()];
}

function openForm() {
    resetRows();
    showForm.value = true;
}

function cancelForm() {
    showForm.value = false;
    error.value = '';
    resetRows();
}

function addRow() {
    rows.value.push(createRow());
}

function removeRow(index) {
    rows.value.splice(index, 1);
}

async function save() {
    const entries = rows.value
        .map((row) => ({
            commented_at: row.commented_at,
            comment: row.comment?.trim() ?? '',
            author_name: authorName.value !== '—' ? authorName.value : '',
        }))
        .filter((row) => row.comment);

    if (!entries.length) {
        error.value = 'Saisissez une réponse.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await api.post(`/recommendations/action-plans/${props.planId}/comments`, { entries });
        cancelForm();
        emit('saved');
    } catch (err) {
        error.value = err.response?.data?.message?.[0]
            ?? err.response?.data?.entries?.[0]?.[0]
            ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

watch(() => props.planId, cancelForm);
</script>
