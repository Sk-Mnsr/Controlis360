<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 text-left"
            :class="[triggerClass, { 'cursor-not-allowed opacity-60': disabled }]"
            :disabled="disabled"
            @click="toggle"
        >
            <span class="min-w-0 flex-1 truncate" :class="displayLabel ? 'text-slate-800' : 'text-slate-400'">
                {{ displayLabel || placeholder }}
            </span>
            <svg
                class="h-4 w-4 shrink-0 text-slate-500 transition-transform"
                :class="{ 'rotate-180': open }"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div
            v-if="open"
            class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-300 bg-white py-1 shadow-lg"
        >
            <p v-if="!options.length" class="px-3 py-2 text-sm text-slate-500">
                {{ emptyText }}
            </p>

            <template v-else>
                <label
                    class="sticky top-0 z-10 flex cursor-pointer items-center gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800"
                    @click.stop
                >
                    <input
                        type="checkbox"
                        class="rounded border-slate-300"
                        :checked="allSelected"
                        :indeterminate.prop="someSelected && !allSelected"
                        @change="toggleSelectAll($event.target.checked)"
                    />
                    <span>{{ allSelected ? 'Tout désélectionner' : 'Tout sélectionner' }}</span>
                    <span class="ml-auto text-xs font-normal text-slate-500">
                        {{ normalizedValue.length }}/{{ allOptionIds.length }}
                    </span>
                </label>

                <template v-for="(group, groupIndex) in groupedOptions" :key="group.label || `group-${groupIndex}`">
                    <div
                        v-if="group.label"
                        class="sticky top-9 z-[5] flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/95 px-3 py-1.5"
                    >
                        <p class="text-[0.68rem] font-bold uppercase tracking-wide text-slate-500">
                            {{ group.label }}
                        </p>
                        <button
                            type="button"
                            class="text-[0.65rem] font-semibold uppercase tracking-wide text-sky-700 hover:text-sky-900"
                            @click.stop="toggleGroup(group)"
                        >
                            {{ isGroupFullySelected(group) ? 'Aucun' : 'Tous' }}
                        </button>
                    </div>
                    <label
                        v-for="option in group.items"
                        :key="optionValue(option)"
                        class="flex cursor-pointer items-start gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click.stop
                    >
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300"
                            :checked="isSelected(option)"
                            @change="onToggle(option, $event.target.checked)"
                        />
                        <span>{{ optionLabel(option) }}</span>
                    </label>
                </template>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Sélectionner' },
    emptyText: { type: String, default: 'Aucune option disponible' },
    disabled: { type: Boolean, default: false },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'name' },
    groupKey: { type: String, default: 'group' },
    triggerClass: {
        type: String,
        default: 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const root = ref(null);
const open = ref(false);

const normalizedValue = computed(() => props.modelValue.map((id) => Number(id)));

const allOptionIds = computed(() =>
    props.options.map((option) => optionValue(option)).filter((id) => !Number.isNaN(id)),
);

const allSelected = computed(
    () =>
        allOptionIds.value.length > 0 &&
        allOptionIds.value.every((id) => normalizedValue.value.includes(id)),
);

const someSelected = computed(() =>
    allOptionIds.value.some((id) => normalizedValue.value.includes(id)),
);

const groupedOptions = computed(() => {
    if (!props.options.length) {
        return [];
    }

    const hasGroups = props.options.some((option) => option?.[props.groupKey]);

    if (!hasGroups) {
        return [{ label: null, items: props.options }];
    }

    const groups = new Map();

    for (const option of props.options) {
        const label = option?.[props.groupKey] || 'Autres';

        if (!groups.has(label)) {
            groups.set(label, []);
        }

        groups.get(label).push(option);
    }

    return [...groups.entries()].map(([label, items]) => ({ label, items }));
});

const displayLabel = computed(() => {
    const labels = normalizedValue.value
        .map((id) => {
            const option = props.options.find((item) => optionValue(item) === id);
            return option ? optionLabel(option) : null;
        })
        .filter(Boolean);

    return labels.join(', ');
});

function optionValue(option) {
    return Number(option?.[props.valueKey]);
}

function optionLabel(option) {
    return option?.[props.labelKey] ?? '';
}

function isSelected(option) {
    return normalizedValue.value.includes(optionValue(option));
}

function emitSelection(next) {
    emit('update:modelValue', next);
    emit('change', next);
}

function onToggle(option, checked) {
    const id = optionValue(option);
    const next = checked
        ? [...new Set([...normalizedValue.value, id])]
        : normalizedValue.value.filter((value) => value !== id);

    emitSelection(next);
}

function toggleSelectAll(checked) {
    emitSelection(checked ? [...allOptionIds.value] : []);
}

function groupIds(group) {
    return group.items.map((option) => optionValue(option)).filter((id) => !Number.isNaN(id));
}

function isGroupFullySelected(group) {
    const ids = groupIds(group);
    return ids.length > 0 && ids.every((id) => normalizedValue.value.includes(id));
}

function toggleGroup(group) {
    const ids = groupIds(group);
    if (!ids.length) return;

    if (isGroupFullySelected(group)) {
        const remove = new Set(ids);
        emitSelection(normalizedValue.value.filter((id) => !remove.has(id)));
        return;
    }

    emitSelection([...new Set([...normalizedValue.value, ...ids])]);
}

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function onDocumentClick(event) {
    if (!open.value) return;
    if (root.value && !root.value.contains(event.target)) {
        close();
    }
}

function onEscape(event) {
    if (event.key === 'Escape') {
        close();
    }
}

watch(() => props.disabled, (isDisabled) => {
    if (isDisabled) close();
});

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onEscape);
});
</script>
