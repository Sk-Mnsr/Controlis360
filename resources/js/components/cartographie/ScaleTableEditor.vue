<template>
    <section class="scale-editor-section">
        <div class="scale-editor-header">
            <h3 class="scale-editor-title" :class="`scale-editor-title-${variant}`">{{ title }}</h3>
            <button type="button" class="scale-editor-add" @click="$emit('add-row')">
                + Ajouter une ligne
            </button>
        </div>

        <p v-if="!rows.length" class="scale-editor-empty">Aucune ligne. Ajoutez au moins un niveau.</p>

        <div v-for="(row, index) in rows" :key="rowKey(row, index)" class="scale-editor-row">
            <div class="scale-editor-level">
                <label class="scale-editor-label">{{ levelLabel }}</label>
                <input
                    v-model.number="row.level"
                    type="number"
                    min="1"
                    max="99"
                    required
                    class="scale-editor-input"
                />
            </div>

            <div class="scale-editor-field">
                <label class="scale-editor-label">Qualification de l'échelle</label>
                <input v-model="row.qualification" required class="scale-editor-input" />
            </div>

            <div class="scale-editor-field scale-editor-field-wide">
                <label class="scale-editor-label">
                    Explication de l'échelle de {{ variant === 'gravity' ? 'gravité' : 'probabilité' }}
                </label>
                <textarea v-model="row.description" required rows="4" class="scale-editor-textarea" />
            </div>

            <button
                type="button"
                class="scale-editor-remove"
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
    title: { type: String, required: true },
    levelLabel: { type: String, required: true },
    variant: { type: String, required: true },
    rows: { type: Array, required: true },
});

defineEmits(['add-row', 'remove-row']);

function rowKey(row, index) {
    return row.id ?? `new-${index}-${row.level}`;
}
</script>

<style scoped>
.scale-editor-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.scale-editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.scale-editor-title {
    flex: 1;
    padding: 0.65rem 0.85rem;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #111111;
}

.scale-editor-title-gravity {
    background: #f4a460;
}

.scale-editor-title-probability {
    background: #f5e6a3;
}

.scale-editor-add {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.scale-editor-add:hover {
    background: #f8fafc;
}

.scale-editor-empty {
    font-size: 0.875rem;
    color: #64748b;
}

.scale-editor-row {
    display: grid;
    gap: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 1rem;
}

@media (min-width: 900px) {
    .scale-editor-row {
        grid-template-columns: 5rem 14rem 1fr auto;
        align-items: start;
    }
}

.scale-editor-level,
.scale-editor-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.scale-editor-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.scale-editor-input,
.scale-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.scale-editor-textarea {
    resize: vertical;
}

.scale-editor-remove {
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

.scale-editor-remove:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
