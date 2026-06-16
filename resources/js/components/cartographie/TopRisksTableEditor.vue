<template>
    <section class="top-risks-editor">
        <div class="top-risks-editor-header">
            <h3 class="top-risks-editor-title">{{ title }}</h3>
            <button type="button" class="top-risks-editor-add" @click="$emit('add-row')">
                + Ajouter une ligne
            </button>
        </div>

        <div v-for="(row, index) in rows" :key="rowKey(row, index)" class="top-risks-editor-row">
            <div class="top-risks-editor-field">
                <label class="top-risks-editor-label">Processus</label>
                <input v-model="row.process_name" class="top-risks-editor-input" />
            </div>

            <div class="top-risks-editor-field">
                <label class="top-risks-editor-label">Sous processus</label>
                <input v-model="row.sub_process_name" required class="top-risks-editor-input" />
            </div>

            <div class="top-risks-editor-field top-risks-editor-field-wide">
                <label class="top-risks-editor-label">Exceptions majeures constatées</label>
                <textarea v-model="row.major_exceptions" rows="2" class="top-risks-editor-textarea" />
            </div>

            <div class="top-risks-editor-field">
                <label class="top-risks-editor-label">Famille de risque</label>
                <input v-model="row.risk_family" class="top-risks-editor-input" />
            </div>

            <div class="top-risks-editor-scores">
                <div class="top-risks-editor-field">
                    <label class="top-risks-editor-label">Gravité (G)</label>
                    <input
                        v-model.number="row.gravity"
                        type="number"
                        min="1"
                        max="6"
                        class="top-risks-editor-input"
                    />
                </div>
                <div class="top-risks-editor-field">
                    <label class="top-risks-editor-label">Probabilité (P)</label>
                    <input
                        v-model.number="row.probability"
                        type="number"
                        min="1"
                        max="6"
                        class="top-risks-editor-input"
                    />
                </div>
                <div class="top-risks-editor-rb">
                    <span class="top-risks-editor-label">Rb (G × P)</span>
                    <strong>{{ grossRisk(row) ?? '—' }}</strong>
                </div>
            </div>

            <button
                type="button"
                class="top-risks-editor-remove"
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
    title: { type: String, default: 'RISQUES OPERATIONNELS A FORT IMPACT BUSINESS' },
    rows: { type: Array, required: true },
});

defineEmits(['add-row', 'remove-row']);

function rowKey(row, index) {
    return row.id ?? `new-${index}-${row.sub_process_name}`;
}

function grossRisk(row) {
    const gravity = Number(row.gravity);
    const probability = Number(row.probability);

    if (!gravity || !probability) {
        return null;
    }

    return gravity * probability;
}
</script>

<style scoped>
.top-risks-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.top-risks-editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.top-risks-editor-title {
    flex: 1;
    background: #c00000;
    padding: 0.65rem 0.85rem;
    text-align: center;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #ffffff;
}

.top-risks-editor-add {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.top-risks-editor-row {
    display: grid;
    gap: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 1rem;
}

@media (min-width: 1000px) {
    .top-risks-editor-row {
        grid-template-columns: 1fr 1fr 2fr 1fr auto auto;
        align-items: start;
    }

    .top-risks-editor-field-wide {
        grid-column: span 1;
    }

    .top-risks-editor-scores {
        grid-column: span 1;
    }
}

.top-risks-editor-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.top-risks-editor-scores {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
    align-items: end;
}

.top-risks-editor-rb {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding-top: 1.35rem;
    text-align: center;
}

.top-risks-editor-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.top-risks-editor-input,
.top-risks-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.top-risks-editor-textarea {
    resize: vertical;
}

.top-risks-editor-remove {
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

.top-risks-editor-remove:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
