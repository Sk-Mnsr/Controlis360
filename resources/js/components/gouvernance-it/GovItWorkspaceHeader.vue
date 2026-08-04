<template>
    <div class="flex items-start justify-between gap-4">
        <table class="border-collapse text-sm">
            <tbody>
                <tr>
                    <th class="border border-slate-500 bg-slate-600 px-3 py-1.5 text-left font-semibold text-white">
                        Filiale
                    </th>
                    <td class="border border-slate-500 bg-white px-3 py-1.5 font-medium text-slate-900">
                        <div v-if="selectable" ref="dropdownRef" class="relative inline-block min-w-[12rem]">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded border border-slate-300 bg-white px-2 py-1 text-left text-sm font-medium text-slate-900 outline-none hover:border-slate-400 focus:border-slate-500 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loading || !filiales.length"
                                :aria-expanded="open"
                                aria-haspopup="listbox"
                                @click="open = !open"
                            >
                                <img
                                    v-if="selectedFlagUrl"
                                    :src="selectedFlagUrl"
                                    alt=""
                                    class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover shadow-sm"
                                />
                                <span class="flex-1 truncate">{{ selectedLabel }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <ul
                                v-if="open"
                                class="absolute left-0 z-20 mt-1 max-h-56 w-full min-w-[12rem] overflow-auto rounded border border-slate-200 bg-white py-1 shadow-lg"
                                role="listbox"
                            >
                                <li
                                    v-for="item in filiales"
                                    :key="item.id"
                                    role="option"
                                    class="flex cursor-pointer items-center gap-2 px-2 py-1.5 text-sm hover:bg-slate-100"
                                    :class="{ 'bg-slate-50 font-semibold': Number(item.id) === Number(modelValue) }"
                                    :aria-selected="Number(item.id) === Number(modelValue)"
                                    @click="selectFiliale(item.id)"
                                >
                                    <img
                                        v-if="flagUrlFor(item)"
                                        :src="flagUrlFor(item)"
                                        alt=""
                                        class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover shadow-sm"
                                    />
                                    <span>{{ item.name }}</span>
                                </li>
                            </ul>
                        </div>
                        <span v-else class="inline-flex items-center gap-2">
                            <img
                                v-if="displayFlagUrl"
                                :src="displayFlagUrl"
                                alt=""
                                class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover shadow-sm"
                            />
                            <span>{{ loading ? '…' : filiale }}</span>
                        </span>
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { countryFlagUrl } from '../../utils/countryFlag';

const props = defineProps({
    filiale: {
        type: String,
        default: '—',
    },
    filialeCode: {
        type: String,
        default: '',
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

const open = ref(false);
const dropdownRef = ref(null);

const selectedItem = computed(() =>
    props.filiales.find((item) => Number(item.id) === Number(props.modelValue)) ?? null,
);

const selectedLabel = computed(() => {
    if (props.loading) {
        return '…';
    }

    return selectedItem.value?.name ?? (props.filiales.length ? '—' : '—');
});

const selectedFlagUrl = computed(() => flagUrlFor(selectedItem.value));

const displayFlagUrl = computed(() => {
    if (props.filialeCode) {
        return countryFlagUrl(props.filialeCode);
    }

    return countryFlagUrl(props.filiale);
});

function flagUrlFor(item) {
    if (!item) {
        return null;
    }

    return countryFlagUrl(item.code || item.name);
}

function selectFiliale(id) {
    const value = id ? Number(id) : null;
    open.value = false;
    emit('update:modelValue', value);
    emit('change', value);
}

function onDocumentClick(event) {
    if (!dropdownRef.value) {
        return;
    }

    if (!dropdownRef.value.contains(event.target)) {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
});
</script>
