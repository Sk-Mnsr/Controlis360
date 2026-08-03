<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="relative mx-auto h-36 w-36 shrink-0 sm:mx-0">
            <svg viewBox="0 0 120 120" class="h-full w-full" aria-hidden="true">
                <circle cx="60" cy="60" r="42" fill="none" stroke="#f1f5f9" stroke-width="16" />
                <circle
                    v-for="segment in segments"
                    :key="segment.code"
                    cx="60"
                    cy="60"
                    r="42"
                    fill="none"
                    :stroke="segment.color"
                    stroke-width="16"
                    :stroke-dasharray="segment.dashArray"
                    :stroke-dashoffset="segment.dashOffset"
                    transform="rotate(-90 60 60)"
                />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-xl font-bold text-slate-900">{{ total }}</span>
                <span class="text-[10px] uppercase tracking-wide text-slate-500">{{ totalLabel }}</span>
            </div>
        </div>

        <ul class="min-w-0 flex-1 space-y-2">
            <li
                v-for="item in items"
                :key="item.code"
                class="flex items-center gap-2 text-sm"
            >
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: item.color }" />
                <span class="min-w-0 flex-1 truncate text-slate-600">{{ item.name }}</span>
                <span class="shrink-0 font-semibold text-slate-800">{{ item.percent }}%</span>
            </li>
            <li v-if="!items.length" class="text-sm text-slate-500">Aucune donnée</li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    totalLabel: { type: String, default: 'total' },
});

const circumference = 2 * Math.PI * 42;

const segments = computed(() => {
    let offset = 0;

    return props.items.map((item) => {
        const length = ((item.percent || 0) / 100) * circumference;
        const segment = {
            ...item,
            dashArray: `${length} ${circumference - length}`,
            dashOffset: -offset,
        };
        offset += length;
        return segment;
    });
});
</script>
