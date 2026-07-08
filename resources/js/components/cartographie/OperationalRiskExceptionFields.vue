<template>
    <table class="risk-form-table" :class="{ 'risk-form-table-readonly': readonly }">
        <thead>
            <tr>
                <th class="risk-form-head">Date ligne</th>
                <th class="risk-form-head">Exceptions majeures</th>
                <th class="risk-form-head">Risques corrélés</th>
                <th class="risk-form-head risk-form-head-family">Famille de risque</th>
                <th class="risk-form-head risk-form-head-score">G</th>
                <th class="risk-form-head risk-form-head-score">P</th>
                <th class="risk-form-head risk-form-head-score">Rb</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="risk-form-cell risk-form-cell-date">
                    <input
                        v-model="model.line_date"
                        type="date"
                        class="risk-form-input"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell">
                    <textarea
                        v-model="model.major_exceptions"
                        rows="1"
                        required
                        class="risk-form-textarea"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell">
                    <textarea
                        v-model="model.correlated_risks"
                        rows="1"
                        class="risk-form-textarea"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell">
                    <select v-model="model.risk_family" class="risk-form-select" :disabled="readonly">
                        <option value="">—</option>
                        <option v-for="family in riskFamilies" :key="family" :value="family">
                            {{ family }}
                        </option>
                    </select>
                </td>
                <td class="risk-form-cell risk-form-cell-score">
                    <input
                        v-model.number="model.gravity"
                        type="number"
                        min="1"
                        max="6"
                        class="risk-form-input risk-form-input-center"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell risk-form-cell-score">
                    <input
                        v-model.number="model.probability"
                        type="number"
                        min="1"
                        max="6"
                        class="risk-form-input risk-form-input-center"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell risk-form-cell-rb">
                    <div class="risk-form-rb" :style="rbStyle">{{ rbScore ?? '—' }}</div>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
import { computed } from 'vue';
import { classificationForCell, grossRiskScore, scoreStyle } from '../../utils/riskScore';

const model = defineModel({ type: Object, required: true });

const props = defineProps({
    riskFamilies: { type: Array, default: () => [] },
    riskClassifications: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
});

const rbScore = computed(() => grossRiskScore(model.value.gravity, model.value.probability));

const rbClassification = computed(() => classificationForCell(
    model.value.gravity,
    model.value.probability,
    props.riskClassifications,
));

const rbStyle = computed(() => scoreStyle(rbClassification.value));
</script>

<style scoped>
@import './risk-form-table.css';

.risk-form-cell-date {
    width: 9rem;
    min-width: 9rem;
}
</style>
