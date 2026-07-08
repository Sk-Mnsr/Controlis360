<template>
    <div class="rounded-xl border p-5" :class="panelClasses">
        <h4 class="text-sm font-semibold uppercase tracking-wide" :class="titleClasses">{{ title }}</h4>
        <p v-if="hint" class="mt-1 text-xs" :class="hintClasses">{{ hint }}</p>

        <div v-if="sortedComments.length" class="mt-4 space-y-2">
            <div
                v-for="item in sortedComments"
                :key="item.id"
                class="rounded-lg border border-blue-100 bg-white p-3"
            >
                <p class="text-xs font-medium text-slate-500">
                    {{ item.author_name || 'Régulateur' }}
                    <span class="font-normal">— {{ formatDisplayDate(item.commented_at) }}</span>
                </p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ item.comment }}</p>
            </div>
        </div>

        <p v-else-if="showEmpty" class="mt-3 text-sm" :class="hintClasses">
            {{ emptyLabel }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    comments: { type: Array, default: () => [] },
    title: { type: String, default: 'Avis du régulateur' },
    hint: { type: String, default: '' },
    emptyLabel: { type: String, default: 'En attente de l\'avis du régulateur.' },
    showEmpty: { type: Boolean, default: true },
    variant: { type: String, default: 'avis' },
});

const panelClasses = computed(() => (
    props.variant === 'transmission'
        ? 'border-emerald-200 bg-emerald-50/50'
        : 'border-blue-200 bg-blue-50/50'
));

const titleClasses = computed(() => (
    props.variant === 'transmission' ? 'text-emerald-900' : 'text-blue-900'
));

const hintClasses = computed(() => (
    props.variant === 'transmission' ? 'text-emerald-800' : 'text-blue-800'
));

const sortedComments = computed(() => [...(props.comments ?? [])].sort((a, b) => {
    const dateCompare = String(b.commented_at ?? '').localeCompare(String(a.commented_at ?? ''));
    if (dateCompare !== 0) return dateCompare;

    return Number(b.id ?? 0) - Number(a.id ?? 0);
}));

function formatDisplayDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}
</script>
