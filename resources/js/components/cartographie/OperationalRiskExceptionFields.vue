<template>
    <table class="risk-form-table risk-exception-table" :class="{ 'risk-form-table-readonly': readonly }">
        <colgroup>
            <col style="width: 7%" />
            <col style="width: 34%" />
            <col style="width: 22%" />
            <col style="width: 20%" />
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
                <td class="risk-form-cell risk-form-cell-correlated">
                    <details
                        v-if="hasDetailOptions"
                        ref="correlatedDetails"
                        class="risk-correlated-picker"
                        :class="{ 'is-readonly': readonly }"
                        @toggle="onCorrelatedToggle"
                    >
                        <summary
                            class="risk-correlated-trigger"
                            :title="model.correlated_risks || ''"
                            :tabindex="readonly ? -1 : 0"
                        >
                            <span class="risk-correlated-label">
                                {{ model.correlated_risks || '— Sélectionner —' }}
                            </span>
                            <span class="risk-correlated-chevron" aria-hidden="true">▾</span>
                        </summary>
                        <div class="risk-correlated-menu" role="listbox">
                            <button
                                type="button"
                                class="risk-correlated-option is-empty"
                                role="option"
                                @click="pickDetail('')"
                            >
                                — Sélectionner —
                            </button>
                            <template v-for="category in riskCategories" :key="category.id ?? category.name">
                                <p class="risk-correlated-group">{{ category.name }}</p>
                                <button
                                    v-for="family in category.families || []"
                                    :key="family.id ?? family.name"
                                    type="button"
                                    class="risk-correlated-option"
                                    :class="{ 'is-selected': model.correlated_risks === family.name }"
                                    role="option"
                                    @click="pickDetail(family.name)"
                                >
                                    {{ family.name }}
                                </button>
                            </template>
                        </div>
                    </details>
                    <details
                        v-else-if="riskFamilies.length"
                        ref="correlatedDetails"
                        class="risk-correlated-picker"
                        :class="{ 'is-readonly': readonly }"
                        @toggle="onCorrelatedToggle"
                    >
                        <summary
                            class="risk-correlated-trigger"
                            :title="model.correlated_risks || ''"
                            :tabindex="readonly ? -1 : 0"
                        >
                            <span class="risk-correlated-label">
                                {{ model.correlated_risks || '— Sélectionner —' }}
                            </span>
                            <span class="risk-correlated-chevron" aria-hidden="true">▾</span>
                        </summary>
                        <div class="risk-correlated-menu" role="listbox">
                            <button
                                type="button"
                                class="risk-correlated-option is-empty"
                                role="option"
                                @click="pickLegacyDetail('')"
                            >
                                — Sélectionner —
                            </button>
                            <button
                                v-for="family in riskFamilies"
                                :key="family"
                                type="button"
                                class="risk-correlated-option"
                                :class="{ 'is-selected': model.correlated_risks === family }"
                                role="option"
                                @click="pickLegacyDetail(family)"
                            >
                                {{ family }}
                            </button>
                        </div>
                    </details>
                    <textarea
                        v-else
                        v-model="model.correlated_risks"
                        rows="2"
                        required
                        class="risk-form-textarea"
                        :readonly="readonly"
                    />
                    <input
                        type="text"
                        class="risk-correlated-required"
                        tabindex="-1"
                        aria-hidden="true"
                        :value="model.correlated_risks || ''"
                        required
                    />
                </td>
                <td class="risk-form-cell risk-form-cell-family">
                    <div
                        class="risk-form-family-display"
                        :title="model.risk_family || 'Remplie automatiquement selon le détail sélectionné'"
                    >
                        {{ model.risk_family || '—' }}
                    </div>
                    <input
                        type="text"
                        class="risk-correlated-required"
                        tabindex="-1"
                        aria-hidden="true"
                        :value="model.risk_family || ''"
                        required
                        readonly
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
import { computed, ref } from 'vue';
import { classificationForCell, grossRiskScore, scoreStyle } from '../../utils/riskScore';

const model = defineModel({ type: Object, required: true });

const props = defineProps({
    riskCategories: { type: Array, default: () => [] },
    riskFamilies: { type: Array, default: () => [] },
    riskClassifications: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
});

const correlatedDetails = ref(null);

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

function closeCorrelatedMenu() {
    const el = correlatedDetails.value;
    if (el) {
        el.open = false;
    }
}

function onCorrelatedToggle(event) {
    if (props.readonly) {
        event.target.open = false;
    }
}

function pickDetail(detailName) {
    model.value.correlated_risks = detailName;
    model.value.risk_family = categoryNameForDetail(detailName);
    closeCorrelatedMenu();
}

function pickLegacyDetail(detailName) {
    model.value.correlated_risks = detailName;
    if (!model.value.risk_family) {
        model.value.risk_family = detailName;
    }
    closeCorrelatedMenu();
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-exception-table {
    table-layout: fixed;
    width: 100%;
}

.risk-form-textarea-risk {
    min-height: 5.5rem;
    resize: vertical;
}

.risk-exception-table :deep(.risk-form-cell-score),
.risk-exception-table :deep(.risk-form-cell-rb) {
    width: auto;
}

.risk-form-cell-correlated {
    position: relative;
    vertical-align: top;
}

.risk-form-cell-family {
    position: relative;
    vertical-align: top;
    background: #f8fafc;
}

.risk-form-family-display {
    min-height: 2.75rem;
    padding: 0.45rem 0.5rem;
    line-height: 1.35;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    color: #111111;
    box-sizing: border-box;
}

.risk-correlated-picker {
    position: relative;
    width: 100%;
}

.risk-correlated-picker.is-readonly {
    pointer-events: none;
}

.risk-correlated-trigger {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    width: 100%;
    min-height: 2.75rem;
    padding: 0.45rem 0.5rem;
    list-style: none;
    cursor: pointer;
    box-sizing: border-box;
}

.risk-correlated-trigger::-webkit-details-marker {
    display: none;
}

.risk-correlated-label {
    flex: 1 1 auto;
    min-width: 0;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    line-height: 1.35;
    color: #111111;
}

.risk-correlated-chevron {
    flex: 0 0 auto;
    margin-top: 0.1rem;
    font-size: 0.7rem;
    color: #64748b;
    line-height: 1;
}

.risk-correlated-menu {
    position: absolute;
    z-index: 40;
    left: 0;
    right: 0;
    top: calc(100% - 1px);
    max-height: 14rem;
    overflow-y: auto;
    border: 1px solid #111111;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
}

.risk-correlated-group {
    margin: 0;
    padding: 0.4rem 0.55rem 0.2rem;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    background: #f8fafc;
}

.risk-correlated-option {
    display: block;
    width: 100%;
    border: none;
    background: #ffffff;
    text-align: left;
    padding: 0.45rem 0.55rem;
    font-size: 0.75rem;
    font-family: inherit;
    line-height: 1.35;
    white-space: normal;
    overflow-wrap: anywhere;
    color: #111111;
    cursor: pointer;
}

.risk-correlated-option:hover,
.risk-correlated-option.is-selected {
    background: #fff7ed;
}

.risk-correlated-option.is-empty {
    color: #64748b;
}

.risk-correlated-required {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
</style>
