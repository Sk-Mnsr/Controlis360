<template>
    <div class="flex gap-3 py-1">
        <dt class="w-36 shrink-0 text-xs font-medium uppercase tracking-wide text-slate-500">{{ label }}</dt>
        <dd class="min-w-0 flex-1 whitespace-pre-wrap text-slate-800">{{ value || '—' }}</dd>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
});
</script>
