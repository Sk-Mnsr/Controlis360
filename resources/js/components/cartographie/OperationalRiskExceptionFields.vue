<template>
    <table class="risk-form-table risk-exception-table" :class="{ 'risk-form-table-readonly': readonly }">
        <colgroup>
            <col style="width: 7%" />
            <col style="width: 42%" />
            <col style="width: 22%" />
            <col style="width: 12%" />
            <col style="width: 5.5%" />
            <col style="width: 5.5%" />
            <col style="width: 6%" />
        </colgroup>
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
                <td class="risk-form-cell">
                    <input
                        v-model="model.line_date"
                        type="date"
                        required
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
                        class="risk-form-select risk-form-select-correlated"
                        :value="model.correlated_risks || ''"
                        :disabled="readonly"
                        required
                        :title="model.correlated_risks || ''"
                        @change="onDetailSelected"
                    >
                        <option value="">— Sélectionner —</option>
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
                        class="risk-form-select risk-form-select-correlated"
                        :value="model.correlated_risks || ''"
                        :disabled="readonly"
                        required
                        :title="model.correlated_risks || ''"
                        @change="onLegacyDetailSelected"
                    >
                        <option value="">— Sélectionner —</option>
                        <option v-for="family in riskFamilies" :key="family" :value="family">
                            {{ family }}
                        </option>
                    </select>
                    <textarea
                        v-else
                        v-model="model.correlated_risks"
                        rows="1"
                        required
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
                        required
                        tabindex="-1"
                        placeholder="—"
                        :title="model.risk_family || 'Remplie automatiquement selon le détail sélectionné'"
                    />
                </td>
                <td class="risk-form-cell risk-form-cell-score">
                    <select
                        class="risk-form-select risk-form-input-center"
                        :value="model.gravity ?? ''"
                        :disabled="readonly"
                        required
                        @change="setScore('gravity', $event.target.value)"
                    >
                        <option value="">—</option>
                        <option v-for="level in scoreLevels" :key="`g-${level}`" :value="level">
                            {{ level }}
                        </option>
                    </select>
                </td>
                <td class="risk-form-cell risk-form-cell-score">
                    <select
                        class="risk-form-select risk-form-input-center"
                        :value="model.probability ?? ''"
                        :disabled="readonly"
                        required
                        @change="setScore('probability', $event.target.value)"
                    >
                        <option value="">—</option>
                        <option v-for="level in scoreLevels" :key="`p-${level}`" :value="level">
                            {{ level }}
                        </option>
                    </select>
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

const scoreLevels = [1, 2, 3, 4, 5, 6];

function setScore(field, rawValue) {
    if (rawValue === '' || rawValue === null || rawValue === undefined) {
        model.value[field] = null;
        return;
    }

    const value = Number(rawValue);
    if (!Number.isFinite(value)) {
        model.value[field] = null;
        return;
    }

    model.value[field] = Math.min(6, Math.max(1, Math.round(value)));
}

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

.risk-exception-table {
    table-layout: fixed;
    width: 100%;
}

.risk-form-select-correlated {
    white-space: normal;
    height: auto;
    min-height: 2.5rem;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.risk-form-textarea-risk {
    min-height: 5.5rem;
    resize: vertical;
}

.risk-exception-table :deep(.risk-form-cell-score),
.risk-exception-table :deep(.risk-form-cell-rb) {
    width: auto;
}
</style>
