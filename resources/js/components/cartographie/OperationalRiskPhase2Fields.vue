<template>
    <div class="phase2-fields">
        <div class="phase2-grid">
            <div class="phase2-wide">
                <label class="phase2-label">Description du dispositif</label>
                <textarea v-model="model.control_description" rows="2" class="phase2-textarea" />
            </div>
            <div>
                <label class="phase2-label">Dispositif existant</label>
                <select v-model="model.control_exists" class="phase2-input">
                    <option :value="null">—</option>
                    <option :value="true">OUI</option>
                    <option :value="false">NON</option>
                </select>
            </div>
            <div>
                <label class="phase2-label">Owner du contrôle</label>
                <input v-model="model.control_owner" class="phase2-input" />
            </div>
            <div>
                <label class="phase2-label">Efficacité</label>
                <input v-model.number="model.control_effectiveness" type="number" min="1" max="5" class="phase2-input" />
            </div>
            <div>
                <label class="phase2-label">Gravité résiduelle (G)</label>
                <div class="phase2-readonly">{{ residualGravity ?? '—' }}</div>
            </div>
            <div>
                <label class="phase2-label">Probabilité résiduelle (Pr)</label>
                <div class="phase2-readonly">{{ formatRiskScore(residualPr) ?? '—' }}</div>
            </div>
            <div>
                <label class="phase2-label">Rr</label>
                <div class="phase2-rr" :style="rrStyle">
                    {{ formatRiskScore(rrScore) ?? '—' }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import {
    classificationForCell,
    formatRiskScore,
    residualProbability,
    scoreStyle,
} from '../../utils/riskScore';

const model = defineModel({ type: Object, required: true });

const props = defineProps({
    gravity: { type: Number, default: null },
    probability: { type: Number, default: null },
    riskClassifications: { type: Array, default: () => [] },
});

const residualGravity = computed(() => props.gravity ?? null);

const residualPr = computed(() =>
    residualProbability(props.probability, model.value.control_effectiveness),
);

const rrScore = computed(() => {
    if (residualGravity.value === null || residualPr.value === null) {
        return null;
    }

    return Math.round(residualGravity.value * residualPr.value * 10) / 10;
});

const rrClassification = computed(() => classificationForCell(
    residualGravity.value,
    residualPr.value === null ? null : Math.round(residualPr.value),
    props.riskClassifications,
));

const rrStyle = computed(() => scoreStyle(rrClassification.value));

watch([residualGravity, residualPr], () => {
    model.value.residual_gravity = residualGravity.value;
    model.value.residual_probability = residualPr.value;
}, { immediate: true });
</script>

<style scoped>
.phase2-grid {
    display: grid;
    gap: 0.75rem;
}

@media (min-width: 900px) {
    .phase2-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .phase2-wide {
        grid-column: span 3;
    }
}

.phase2-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.phase2-input,
.phase2-textarea,
.phase2-readonly {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.phase2-readonly {
    min-height: 2.35rem;
    display: flex;
    align-items: center;
    background: #f8fafc;
    color: #334155;
    font-weight: 600;
}

.phase2-rr {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 2.35rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 700;
    background: #f8fafc;
}
</style>
