<template>
    <div>
        <div v-if="!summaryRow && !rows.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
            Aucune recommandation par département.
        </div>

        <div v-else class="h-full overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-red-800 text-white">
                        <th class="reco-panel-header border border-white/20 px-4 py-3 font-semibold">{{ firstColumnLabel }}</th>
                        <th class="reco-panel-header border border-white/20 px-4 py-3 text-center font-semibold">Total recos formulées</th>
                        <th class="reco-panel-header border border-white/20 px-4 py-3 text-center font-semibold">Recos totales implémentées</th>
                        <th class="reco-panel-header border border-white/20 px-4 py-3 text-center font-semibold">Recos en cours d'implémentation</th>
                        <th class="reco-panel-header border border-white/20 px-4 py-3 text-center font-semibold">No start</th>
                        <th class="reco-panel-header border border-white/20 px-4 py-3 text-center font-semibold">
                            {{ summaryRow ? "Taux d'implémentation de la mission" : "Taux d'implémentation" }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-slate-100">
                        <td class="border border-white px-4 py-3 font-medium text-slate-900">
                            <template v-if="summaryRow">
                                {{ summaryRow.label }}
                            </template>
                            <select
                                v-else
                                :value="selectedId ?? ''"
                                class="w-full min-w-[10rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                @change="onDepartmentChange"
                            >
                                <option value="">Sélectionner un département</option>
                                <option
                                    v-for="row in rows"
                                    :key="row.id"
                                    :value="row.id"
                                >
                                    {{ row.name }}
                                </option>
                            </select>
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(activeRow?.total) }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(activeRow?.implemented) }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(activeRow?.in_progress) }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center text-slate-800">
                            {{ displayStat(activeRow?.no_start) }}
                        </td>
                        <td class="border border-white px-4 py-3 text-center font-semibold text-slate-900">
                            {{ activeRow ? `${activeRow.implementation_rate}%` : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { displayStatValue } from '../../utils/reco-stats';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    summaryRow: { type: Object, default: null },
    clickable: { type: Boolean, default: false },
    selectedId: { type: [String, Number], default: null },
});

const emit = defineEmits(['department-click', 'department-clear']);

const firstColumnLabel = computed(() => (props.summaryRow ? 'Référence' : 'Département'));

const selectedRow = computed(() => (
    props.rows.find((row) => String(row.id) === String(props.selectedId)) ?? null
));

const activeRow = computed(() => props.summaryRow ?? selectedRow.value);

function displayStat(value) {
    return displayStatValue(value);
}

function onDepartmentChange(event) {
    const value = event.target.value;

    if (!value) {
        emit('department-clear');
        return;
    }

    const row = props.rows.find((item) => String(item.id) === String(value));
    if (row) {
        emit('department-click', row);
    }
}
</script>

<style scoped>
.reco-panel-header {
    min-height: 4rem;
    vertical-align: middle;
}
</style>
