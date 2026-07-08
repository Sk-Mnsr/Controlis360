<template>
    <table class="risk-form-table" :class="{ 'risk-form-table-readonly': readonly }">
        <thead>
            <tr>
                <th class="risk-form-head">N°</th>
                <th class="risk-form-head">Processus</th>
                <th class="risk-form-head">Ratio</th>
                <th class="risk-form-head">Sous processus</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="risk-form-cell risk-form-cell-narrow">
                    <input v-model.number="model.process_number" type="number" min="1" class="risk-form-input risk-form-input-center" :readonly="readonly" />
                </td>
                <td class="risk-form-cell">
                    <input v-model="model.process_name" class="risk-form-input" :placeholder="departmentName" :readonly="readonly" />
                </td>
                <td class="risk-form-cell risk-form-cell-narrow">
                    <input v-model.number="model.ratio" type="number" min="0" max="100" step="0.01" class="risk-form-input risk-form-input-center" :readonly="readonly" />
                </td>
                <td class="risk-form-cell">
                    <input v-model="model.sub_process_name" required class="risk-form-input" :readonly="readonly" />
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
const model = defineModel({ type: Object, required: true });

defineProps({
    departmentName: { type: String, default: '' },
    readonly: { type: Boolean, default: false },
});
</script>

<style scoped>
@import './risk-form-table.css';
</style>
