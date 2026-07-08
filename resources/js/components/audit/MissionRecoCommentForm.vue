<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <button
                v-if="showBack"
                type="button"
                class="text-xs font-medium text-slate-500 hover:text-slate-800"
                @click="$emit('back')"
            >
                ← Retour
            </button>
            <span v-else />

            <div class="flex flex-wrap items-center justify-end gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 text-lg font-semibold text-emerald-800 hover:bg-emerald-100"
                    title="Ajouter une ligne"
                    @click="addRow"
                >
                    +
                </button>
                <button
                    v-if="rows.length"
                    type="button"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-60"
                    :disabled="saving"
                    @click="save"
                >
                    {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
            </div>
        </div>

        <div v-if="sortedFollowUps.length" class="space-y-2">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Historique</h4>
            <div
                v-for="item in sortedFollowUps"
                :key="item.id"
                class="rounded-lg border border-slate-200 bg-slate-50/80 p-3"
            >
                <p class="text-xs font-medium text-slate-500">
                    {{ item.author_name || 'Utilisateur' }}
                    <span class="font-normal">— {{ formatDisplayDate(item.commented_at) }}</span>
                </p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ item.comment }}</p>
            </div>
        </div>

        <div v-if="rows.length" class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nouveau commentaire</h4>
            <div
                v-for="(row, index) in rows"
                :key="row.key"
                class="rounded-lg border border-slate-200 bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="grid flex-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date</label>
                            <input
                                v-model="row.commented_at"
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
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Commentaire</label>
                            <textarea
                                v-model="row.comment"
                                rows="3"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                placeholder="Saisir un commentaire..."
                            />
                        </div>
                    </div>
                    <button
                        v-if="rows.length > 1"
                        type="button"
                        class="mt-6 rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50"
                        title="Retirer cette ligne"
                        @click="removeRow(index)"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    reco: { type: Object, required: true },
    followUps: { type: Array, default: () => [] },
    showBack: { type: Boolean, default: false },
});

const emit = defineEmits(['saved', 'back']);

const auth = useAuthStore();

const authorName = computed(() => auth.user?.name ?? '—');

const sortedFollowUps = computed(() => [...(props.followUps ?? [])].sort((a, b) => {
    const dateCompare = String(b.commented_at ?? '').localeCompare(String(a.commented_at ?? ''));
    if (dateCompare !== 0) return dateCompare;

    return Number(b.id ?? 0) - Number(a.id ?? 0);
}));

const saving = ref(false);
const error = ref('');
const rows = ref([]);
let rowKey = 1;

function todayIso() {
    return new Date().toISOString().slice(0, 10);
}

function formatDisplayDate(value) {
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
    rows.value = [];
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
        error.value = 'Ajoutez au moins un commentaire.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await api.post(`/recommendations/${props.reco.id}/follow-ups`, { entries });
        resetRows();
        emit('saved');
    } catch (err) {
        error.value = err.response?.data?.message?.[0]
            ?? err.response?.data?.entries?.[0]?.[0]
            ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

watch(() => props.reco?.id, resetRows, { immediate: true });
</script>
