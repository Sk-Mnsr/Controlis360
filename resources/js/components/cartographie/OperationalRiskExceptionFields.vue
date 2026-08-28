<template>
    <table class="risk-form-table" :class="{ 'risk-form-table-readonly': readonly }">
        <thead>
            <tr>
                <th class="risk-form-head">Date ligne</th>
                <th class="risk-form-head">Risques identifiés</th>
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
                        rows="3"
                        required
                        class="risk-form-textarea risk-form-textarea-risk"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell">
                    <select
                        v-if="hasDetailOptions"
                        class="risk-form-select"
                        :value="model.correlated_risks || ''"
                        :disabled="readonly"
                        @change="onDetailSelected"
                    >
                        <option value="">— Sélectionner un détail —</option>
                        <optgroup
                            v-for="category in riskCategories"
                            :key="category.id ?? category.name"
                            :label="category.name"
                        >
                            <option
                                v-for="family in category.families || []"
                                :key="family.id ?? family.name"
                                :value="family.name"
                            >
                                {{ family.name }}
                            </option>
                        </optgroup>
                    </select>
                    <select
                        v-else-if="riskFamilies.length"
                        class="risk-form-select"
                        :value="model.correlated_risks || ''"
                        :disabled="readonly"
                        @change="onLegacyDetailSelected"
                    >
                        <option value="">— Sélectionner un détail —</option>
                        <option v-for="family in riskFamilies" :key="family" :value="family">
                            {{ family }}
                        </option>
                    </select>
                    <textarea
                        v-else
                        v-model="model.correlated_risks"
                        rows="1"
                        class="risk-form-textarea"
                        :readonly="readonly"
                    />
                </td>
                <td class="risk-form-cell">
                    <input
                        :value="model.risk_family || ''"
                        type="text"
                        class="risk-form-input"
                        readonly
                        tabindex="-1"
                        placeholder="—"
                        title="Remplie automatiquement selon le détail sélectionné"
                    />
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
    riskCategories: { type: Array, default: () => [] },
    riskFamilies: { type: Array, default: () => [] },
    riskClassifications: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
});

const hasDetailOptions = computed(() =>
    props.riskCategories.some((category) => (category.families || []).length > 0),
);

const rbScore = computed(() => grossRiskScore(model.value.gravity, model.value.probability));

const rbClassification = computed(() => classificationForCell(
    model.value.gravity,
    model.value.probability,
    props.riskClassifications,
));

const rbStyle = computed(() => scoreStyle(rbClassification.value));

function categoryNameForDetail(detailName) {
    if (!detailName) {
        return '';
    }

    for (const category of props.riskCategories) {
        const match = (category.families || []).find((family) => family.name === detailName);
        if (match) {
            return category.name ?? '';
        }
    }

    return '';
}

function onDetailSelected(event) {
    const detailName = event.target.value;
    model.value.correlated_risks = detailName;
    model.value.risk_family = categoryNameForDetail(detailName);
}

function onLegacyDetailSelected(event) {
    const detailName = event.target.value;
    model.value.correlated_risks = detailName;
    if (!model.value.risk_family) {
        model.value.risk_family = detailName;
    }
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-form-cell-date {
    width: 9rem;
    min-width: 9rem;
}

.risk-form-textarea-risk {
    min-height: 4.5rem;
    resize: vertical;
}
</style>
