<template>
    <div class="flex items-start justify-between gap-4">
        <table class="border-collapse text-sm">
            <tbody>
                <tr>
                    <th class="border border-slate-500 bg-slate-600 px-3 py-1.5 text-left font-semibold text-white">
                        Filiale
                    </th>
                    <td class="border border-slate-500 bg-white px-3 py-1.5 font-medium text-slate-900">
                        <select
                            v-if="selectable"
                            class="min-w-[12rem] rounded border border-slate-300 bg-white px-2 py-1 text-sm font-medium text-slate-900 outline-none focus:border-slate-500"
                            :disabled="loading || !filiales.length"
                            :value="modelValue ?? ''"
                            @change="onFilialeChange"
                        >
                            <option v-if="!filiales.length" value="">—</option>
                            <option
                                v-for="item in filiales"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.name }}
                            </option>
                        </select>
                        <span v-else>{{ loading ? '…' : filiale }}</span>
                    </td>
                </tr>
                <tr>
                    <th class="border border-slate-500 bg-slate-600 px-3 py-1.5 text-left font-semibold text-white">
                        Responsable
                    </th>
                    <td class="border border-slate-500 bg-white px-3 py-1.5 font-medium uppercase tracking-wide text-slate-900">
                        {{ loading ? '…' : responsable }}
                    </td>
                </tr>
                <tr>
                    <th class="border border-slate-500 bg-slate-600 px-3 py-1.5 text-left font-semibold text-white">
                        Team
                    </th>
                    <td class="border border-slate-500 bg-white px-3 py-1.5 font-medium uppercase tracking-wide text-slate-900">
                        {{ loading ? '…' : team }}
                    </td>
                </tr>
            </tbody>
        </table>

        <button
            v-if="showAdd"
            type="button"
            class="shrink-0 px-2 text-4xl font-light leading-none text-red-600 transition hover:text-red-800"
            title="Ajouter un ensemble"
            aria-label="Ajouter un ensemble"
            @click="emit('add')"
        >
            +
        </button>
    </div>
</template>

<script setup>
defineProps({
    filiale: {
        type: String,
        default: '—',
    },
    responsable: {
        type: String,
        default: '—',
    },
    team: {
        type: String,
        default: '—',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    showAdd: {
        type: Boolean,
        default: true,
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    filiales: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
});

const emit = defineEmits(['add', 'update:modelValue', 'change']);

function onFilialeChange(event) {
    const value = event.target.value ? Number(event.target.value) : null;
    emit('update:modelValue', value);
    emit('change', value);
}
</script>
