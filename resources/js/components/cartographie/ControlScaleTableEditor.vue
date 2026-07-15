<template>
    <section class="control-editor-section">
        <div class="control-editor-header">
            <h3 class="control-editor-title">Echelle de contrôle</h3>
            <button type="button" class="control-editor-add" @click="$emit('add-row')">
                + Ajouter une ligne
            </button>
        </div>

        <p v-if="!rows.length" class="control-editor-empty">Aucune ligne. Ajoutez au moins un niveau.</p>

        <div v-for="(row, index) in rows" :key="rowKey(row, index)" class="control-editor-row">
            <div class="control-editor-level">
                <label class="control-editor-label">Niveau</label>
                <input
                    v-model.number="row.level"
                    type="number"
                    min="1"
                    max="99"
                    required
                    class="control-editor-input"
                />
            </div>

            <div class="control-editor-field">
                <label class="control-editor-label">Qualification</label>
                <input v-model="row.qualification" required class="control-editor-input" />
            </div>

            <div class="control-editor-field control-editor-field-wide">
                <label class="control-editor-label">Explicitation</label>
                <textarea v-model="row.description" required rows="4" class="control-editor-textarea" />
            </div>

            <div class="control-editor-field control-editor-field-maturity">
                <label class="control-editor-label">Maturité du contrôle</label>
                <input v-model="row.maturity_label" required class="control-editor-input" />
            </div>

            <button
                type="button"
                class="control-editor-remove"
                :disabled="rows.length <= 1"
                @click="$emit('remove-row', index)"
            >
                Supprimer
            </button>
        </div>
    </section>
</template>

<script setup>
defineProps({
    rows: { type: Array, required: true },
});

defineEmits(['add-row', 'remove-row']);

function rowKey(row, index) {
    return row.id ?? `new-${index}-${row.level}`;
}
</script>

<style scoped>
.control-editor-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.control-editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.control-editor-title {
    flex: 1;
    background: #d1d5db;
    padding: 0.65rem 0.85rem;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #111111;
}

.control-editor-add {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.control-editor-add:hover {
    background: #f8fafc;
}

.control-editor-empty {
    font-size: 0.875rem;
    color: #64748b;
}

.control-editor-row {
    display: grid;
    gap: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 1rem;
}

@media (min-width: 1100px) {
    .control-editor-row {
        grid-template-columns: 5rem 11rem 1fr 10rem auto;
        align-items: start;
    }
}

.control-editor-level,
.control-editor-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.control-editor-field-maturity {
    background: #f1f8f2;
    border-radius: 0.5rem;
    padding: 0.5rem;
}

.control-editor-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.control-editor-input,
.control-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.control-editor-textarea {
    resize: vertical;
}

.control-editor-remove {
    align-self: start;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    background: #fff5f5;
    padding: 0.45rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #b91c1c;
    cursor: pointer;
}

.control-editor-remove:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
