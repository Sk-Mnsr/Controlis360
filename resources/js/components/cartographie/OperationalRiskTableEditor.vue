<template>
    <section class="operational-risk-editor">
        <div class="operational-risk-editor-header">
            <button type="button" class="operational-risk-editor-add" @click="$emit('add-row')">
                + Ajouter une ligne
            </button>
        </div>

        <div v-for="(row, index) in rows" :key="rowKey(row, index)" class="operational-risk-editor-row">
            <div class="operational-risk-editor-grid">
                <div>
                    <label class="operational-risk-editor-label">N°</label>
                    <input v-model.number="row.process_number" type="number" min="1" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Processus</label>
                    <input v-model="row.process_name" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Ratio (%)</label>
                    <input v-model.number="row.ratio" type="number" min="0" max="100" step="0.01" class="operational-risk-editor-input" />
                </div>
                <div class="operational-risk-editor-wide">
                    <label class="operational-risk-editor-label">Sous processus</label>
                    <input v-model="row.sub_process_name" required class="operational-risk-editor-input" />
                </div>
            </div>

            <div class="operational-risk-editor-grid">
                <div class="operational-risk-editor-wide">
                    <label class="operational-risk-editor-label">Exceptions majeures</label>
                    <textarea v-model="row.major_exceptions" rows="2" class="operational-risk-editor-textarea" />
                </div>
                <div class="operational-risk-editor-wide">
                    <label class="operational-risk-editor-label">Risques corrélés</label>
                    <textarea v-model="row.correlated_risks" rows="2" class="operational-risk-editor-textarea" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Famille de risque</label>
                    <input v-model="row.risk_family" class="operational-risk-editor-input" />
                </div>
            </div>

            <div class="operational-risk-editor-grid">
                <div>
                    <label class="operational-risk-editor-label">Gravité (G)</label>
                    <input v-model.number="row.gravity" type="number" min="1" max="6" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Probabilité (P)</label>
                    <input v-model.number="row.probability" type="number" min="1" max="6" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Rb</label>
                    <strong>{{ grossRisk(row) ?? '—' }}</strong>
                </div>
                <div>
                    <label class="operational-risk-editor-label">Gravité résiduelle</label>
                    <input v-model.number="row.residual_gravity" type="number" min="1" max="6" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Probabilité résiduelle</label>
                    <input v-model.number="row.residual_probability" type="number" min="1" max="6" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Rr</label>
                    <strong>{{ residualRisk(row) ?? '—' }}</strong>
                </div>
            </div>

            <div class="operational-risk-editor-grid">
                <div class="operational-risk-editor-wide">
                    <label class="operational-risk-editor-label">Description du dispositif</label>
                    <textarea v-model="row.control_description" rows="2" class="operational-risk-editor-textarea" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Dispositif existant</label>
                    <select v-model="row.control_exists" class="operational-risk-editor-input">
                        <option :value="null">—</option>
                        <option :value="true">OUI</option>
                        <option :value="false">NON</option>
                    </select>
                </div>
                <div>
                    <label class="operational-risk-editor-label">Owner du contrôle</label>
                    <input v-model="row.control_owner" class="operational-risk-editor-input" />
                </div>
                <div>
                    <label class="operational-risk-editor-label">Efficacité</label>
                    <input v-model.number="row.control_effectiveness" type="number" min="1" max="5" class="operational-risk-editor-input" />
                </div>
            </div>

            <button
                type="button"
                class="operational-risk-editor-remove"
                :disabled="rows.length <= 1"
                @click="$emit('remove-row', index)"
            >
                Supprimer la ligne
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
    return row.id ?? `new-${index}-${row.sub_process_name}`;
}

function grossRisk(row) {
    const gravity = Number(row.gravity);
    const probability = Number(row.probability);
    return gravity && probability ? gravity * probability : null;
}

function residualRisk(row) {
    const gravity = Number(row.residual_gravity);
    const probability = Number(row.residual_probability);
    return gravity && probability ? gravity * probability : null;
}
</script>

<style scoped>
.operational-risk-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.operational-risk-editor-header {
    display: flex;
    justify-content: flex-end;
}

.operational-risk-editor-add {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.operational-risk-editor-row {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 1rem;
}

.operational-risk-editor-grid {
    display: grid;
    gap: 0.75rem;
}

@media (min-width: 900px) {
    .operational-risk-editor-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }

    .operational-risk-editor-wide {
        grid-column: span 2;
    }
}

.operational-risk-editor-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.operational-risk-editor-input,
.operational-risk-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.operational-risk-editor-textarea {
    resize: vertical;
}

.operational-risk-editor-remove {
    align-self: flex-start;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    background: #fff5f5;
    padding: 0.45rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #b91c1c;
    cursor: pointer;
}

.operational-risk-editor-remove:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
