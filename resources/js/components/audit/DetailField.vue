<template>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ displayValue }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
});

const displayValue = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') {
        return '—';
    }

    return props.value;
});
</script>
