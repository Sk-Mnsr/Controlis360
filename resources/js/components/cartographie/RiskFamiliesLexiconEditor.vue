<template>
    <section class="risk-families-editor">
        <div class="risk-families-editor-header">
            <h3 class="risk-families-editor-title">Lexique famille de risques</h3>
            <button type="button" class="risk-families-editor-add" @click="addCategory">
                + Ajouter une famille
            </button>
        </div>

        <div v-for="(category, categoryIndex) in localCategories" :key="categoryKey(category, categoryIndex)" class="risk-families-editor-category">
            <div class="risk-families-editor-category-header">
                <span class="risk-families-editor-label">Famille {{ category.number }}</span>
                <button
                    type="button"
                    class="risk-families-editor-remove"
                    :disabled="localCategories.length <= 1"
                    @click="removeCategory(categoryIndex)"
                >
                    Supprimer la famille
                </button>
            </div>

            <div class="risk-families-editor-grid">
                <div>
                    <label class="risk-families-editor-label">N°</label>
                    <input v-model.number="category.number" type="number" min="1" max="99" required class="risk-families-editor-input" />
                </div>
                <div class="risk-families-editor-field-wide">
                    <label class="risk-families-editor-label">Nom de la famille</label>
                    <input v-model="category.name" required class="risk-families-editor-input" />
                </div>
            </div>

            <div>
                <label class="risk-families-editor-label">Description générale</label>
                <textarea v-model="category.description" rows="3" class="risk-families-editor-textarea" />
            </div>

            <div class="risk-families-editor-families">
                <div class="risk-families-editor-families-header">
                    <span class="risk-families-editor-label">Détails par famille</span>
                    <button type="button" class="risk-families-editor-add-small" @click="addFamily(categoryIndex)">
                        + Ajouter un détail
                    </button>
                </div>

                <div
                    v-for="(family, familyIndex) in category.families"
                    :key="familyKey(family, familyIndex)"
                    class="risk-families-editor-family"
                >
                    <input v-model="family.name" required class="risk-families-editor-input" placeholder="Détail" />
                    <textarea
                        v-model="family.definition"
                        rows="2"
                        class="risk-families-editor-textarea"
                        placeholder="Définition"
                    />
                    <button
                        type="button"
                        class="risk-families-editor-remove"
                        :disabled="category.families.length <= 1"
                        @click="removeFamily(categoryIndex, familyIndex)"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    categories: { type: Array, required: true },
});

const emit = defineEmits(['update']);

const localCategories = ref(JSON.parse(JSON.stringify(props.categories)));

watch(
    () => props.categories,
    (value) => {
        localCategories.value = JSON.parse(JSON.stringify(value));
    },
    { deep: true },
);

watch(localCategories, () => emit('update', localCategories.value), { deep: true });

function categoryKey(category, index) {
    return category.id ?? `new-category-${index}-${category.number}`;
}

function familyKey(family, index) {
    return family.id ?? `new-family-${index}-${family.name}`;
}

function nextNumber() {
    const numbers = localCategories.value.map((category) => Number(category.number) || 0);
    return numbers.length ? Math.max(...numbers) + 1 : 1;
}

function addCategory() {
    localCategories.value.push({
        id: null,
        number: nextNumber(),
        name: '',
        description: '',
        families: [{ id: null, name: '', definition: '' }],
    });
}

function removeCategory(index) {
    if (localCategories.value.length <= 1) return;
    localCategories.value.splice(index, 1);
}

function addFamily(categoryIndex) {
    localCategories.value[categoryIndex].families.push({
        id: null,
        name: '',
        definition: '',
    });
}

function removeFamily(categoryIndex, familyIndex) {
    const families = localCategories.value[categoryIndex].families;
    if (families.length <= 1) return;
    families.splice(familyIndex, 1);
}
</script>

<style scoped>
.risk-families-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.risk-families-editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.risk-families-editor-title {
    font-size: 0.95rem;
    font-weight: 700;
    text-transform: uppercase;
}

.risk-families-editor-add,
.risk-families-editor-add-small {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.risk-families-editor-category {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 1rem;
}

.risk-families-editor-category-header,
.risk-families-editor-families-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.risk-families-editor-grid {
    display: grid;
    gap: 0.75rem;
}

@media (min-width: 700px) {
    .risk-families-editor-grid {
        grid-template-columns: 5rem 1fr;
    }
}

.risk-families-editor-field-wide {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.risk-families-editor-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.risk-families-editor-input,
.risk-families-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.risk-families-editor-textarea {
    resize: vertical;
}

.risk-families-editor-families {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
}

.risk-families-editor-family {
    display: grid;
    gap: 0.5rem;
}

@media (min-width: 900px) {
    .risk-families-editor-family {
        grid-template-columns: 14rem 1fr auto;
        align-items: start;
    }
}

.risk-families-editor-remove {
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    background: #fff5f5;
    padding: 0.45rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #b91c1c;
    cursor: pointer;
}

.risk-families-editor-remove:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>
