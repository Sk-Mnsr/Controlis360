<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[85vh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-800">Discussion ligne</h3>
                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        {{ activityTitle || 'Sans titre' }}
                    </p>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Échanges datés entre Agent IT, Responsable IT et Responsable Régional
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

            <div ref="listEl" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-3">
                <p v-if="loading" class="text-center text-xs text-slate-400">Chargement…</p>
                <p v-else-if="error" class="text-center text-xs text-red-600">{{ error }}</p>
                <p v-else-if="!messages.length" class="text-center text-xs text-slate-400">
                    Aucun message pour le moment.
                </p>
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm"
                    :class="message.user_id === currentUserId ? 'border-sky-200 bg-sky-50/60' : ''"
                >
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-xs font-semibold text-slate-800">{{ message.author_name }}</span>
                            <span class="ml-1.5 text-[10px] font-medium text-slate-500">
                                {{ message.author_profile_label }}
                            </span>
                        </div>
                        <time class="shrink-0 text-[10px] text-slate-400">
                            {{ message.created_at_label || formatDateTime(message.created_at) }}
                        </time>
                    </div>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ message.body }}</p>
                </div>
            </div>

            <form class="shrink-0 border-t border-slate-200 bg-white px-4 py-3" @submit.prevent="submit">
                <p v-if="submitError" class="mb-2 text-xs text-red-600">{{ submitError }}</p>
                <textarea
                    v-model="draft"
                    rows="3"
                    class="w-full resize-y rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-400"
                    placeholder="Écrire un message…"
                    :disabled="sending"
                />
                <div class="mt-2 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        @click="$emit('close')"
                    >
                        Fermer
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800 disabled:opacity-50"
                        :disabled="sending || !draft.trim()"
                    >
                        {{ sending ? '…' : 'Envoyer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    open: { type: Boolean, default: false },
    activityId: { type: [Number, String], default: null },
    activityTitle: { type: String, default: '' },
});

const emit = defineEmits(['close', 'updated']);

const auth = useAuthStore();
const currentUserId = computed(() => auth.user?.id);

const messages = ref([]);
const loading = ref(false);
const sending = ref(false);
const error = ref('');
const submitError = ref('');
const draft = ref('');
const listEl = ref(null);

function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

async function scrollToBottom() {
    await nextTick();
    if (listEl.value) {
        listEl.value.scrollTop = listEl.value.scrollHeight;
    }
}

async function loadMessages() {
    if (!props.activityId) return;

    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get(`/gouvernance-it/activities/${props.activityId}/messages`);
        const payload = data.data ?? data ?? {};
        messages.value = payload.messages ?? [];
        await scrollToBottom();
    } catch (err) {
        error.value = err.response?.data?.message
            || Object.values(err.response?.data?.errors ?? {}).flat().join(' ')
            || 'Impossible de charger la discussion.';
        messages.value = [];
    } finally {
        loading.value = false;
    }
}

async function submit() {
    const body = draft.value.trim();
    if (!body || !props.activityId) return;

    sending.value = true;
    submitError.value = '';
    try {
        const { data } = await api.post(`/gouvernance-it/activities/${props.activityId}/messages`, { body });
        const created = data.data ?? data;
        messages.value.push(created);
        draft.value = '';
        emit('updated', { activityId: props.activityId, count: messages.value.length });
        await scrollToBottom();
    } catch (err) {
        submitError.value = err.response?.data?.message
            || Object.values(err.response?.data?.errors ?? {}).flat().join(' ')
            || 'Impossible d’envoyer le message.';
    } finally {
        sending.value = false;
    }
}

watch(
    () => [props.open, props.activityId],
    ([isOpen]) => {
        if (isOpen && props.activityId) {
            draft.value = '';
            submitError.value = '';
            loadMessages();
        }
    },
);
</script>
