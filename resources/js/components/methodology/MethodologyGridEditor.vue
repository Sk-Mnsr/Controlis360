<template>
    <div class="grid-editor">
        <div class="grid-editor-columns">
            <label class="edit-label">Colonnes</label>
            <div class="grid-editor-columns-row">
                <input
                    v-for="(column, index) in localColumns"
                    :key="index"
                    v-model="localColumns[index]"
                    class="edit-input"
                    :placeholder="`Colonne ${index + 1}`"
                />
            </div>
        </div>

        <div v-for="(row, index) in localRows" :key="index" class="grid-editor-row">
            <div class="grid-editor-row-header">
                <span class="edit-label">Ligne {{ index + 1 }}</span>
                <button
                    v-if="localRows.length > 1"
                    type="button"
                    class="grid-remove-btn"
                    @click="removeRow(index)"
                >
                    Supprimer
                </button>
            </div>
            <input v-model="row.label" class="edit-input" placeholder="Principe 1" />
            <textarea v-model="row.statement" rows="2" class="edit-textarea" placeholder="Énoncé du principe" />
            <textarea v-model="row.explanation" rows="4" class="edit-textarea" placeholder="Explications" />
        </div>

        <button type="button" class="grid-add-btn" @click="addRow">+ Ajouter une ligne</button>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const emit = defineEmits(['update']);

const localColumns = ref([...props.columns]);
const localRows = ref(JSON.parse(JSON.stringify(props.rows)));

watch([localColumns, localRows], () => {
    emit('update', {
        columns: localColumns.value,
        rows: localRows.value,
    });
}, { deep: true });

function addRow() {
    localRows.value.push({
        label: `Principe ${localRows.value.length + 1}`,
        statement: '',
        explanation: '',
    });
}

function removeRow(index) {
    localRows.value.splice(index, 1);
}
</script>

<style scoped>
.grid-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.grid-editor-columns-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}

.grid-editor-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
}

.grid-editor-row-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.edit-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.edit-input,
.edit-textarea {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.edit-textarea {
    resize: vertical;
}

.grid-add-btn {
    align-self: flex-start;
    border-radius: 0.5rem;
    border: 1px dashed #94a3b8;
    background: #f8fafc;
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.grid-remove-btn {
    font-size: 0.75rem;
    color: #b91c1c;
}
</style>
