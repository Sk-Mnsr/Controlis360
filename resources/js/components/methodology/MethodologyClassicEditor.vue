<template>
    <div class="definitions-editor">
        <div>
            <label class="definitions-editor-label">Introduction</label>
            <textarea v-model="localIntroduction" rows="4" class="definitions-editor-textarea" />
        </div>

        <div v-for="(section, index) in localSections" :key="index" class="definitions-editor-section">
            <label class="definitions-editor-label">Section {{ index + 1 }} — Titre</label>
            <input v-model="section.title" required class="definitions-editor-input" />

            <label class="definitions-editor-label">Contenu</label>
            <textarea v-model="section.content" rows="3" class="definitions-editor-textarea" />

            <label class="definitions-editor-label">Sous-titre (optionnel)</label>
            <input v-model="section.subtitle" class="definitions-editor-input" />

            <label class="definitions-editor-label">Éléments de liste (un par ligne)</label>
            <textarea
                :value="itemsText[index]"
                rows="4"
                class="definitions-editor-textarea"
                @input="updateItems(index, $event.target.value)"
            />
        </div>

        <div>
            <label class="definitions-editor-label">Conclusion</label>
            <textarea v-model="localConclusion" rows="4" class="definitions-editor-textarea" />
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    introduction: { type: String, default: '' },
    sections: { type: Array, default: () => [] },
    conclusion: { type: String, default: '' },
});

const emit = defineEmits(['update']);

const localIntroduction = ref(props.introduction);
const localSections = ref(JSON.parse(JSON.stringify(props.sections)));
const localConclusion = ref(props.conclusion);
const itemsText = ref(localSections.value.map((section) => (section.items ?? []).join('\n')));

function emitUpdate() {
    emit('update', {
        introduction: localIntroduction.value,
        sections: localSections.value,
        conclusion: localConclusion.value,
    });
}

function updateItems(index, value) {
    itemsText.value[index] = value;
    localSections.value[index].items = value
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
    emitUpdate();
}

watch(
    () => [props.introduction, props.sections, props.conclusion],
    () => {
        localIntroduction.value = props.introduction;
        localSections.value = JSON.parse(JSON.stringify(props.sections));
        localConclusion.value = props.conclusion;
        itemsText.value = localSections.value.map((section) => (section.items ?? []).join('\n'));
    },
    { deep: true },
);

watch([localIntroduction, localSections, localConclusion], emitUpdate, { deep: true });
</script>

<style scoped>
.definitions-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.definitions-editor-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
}

.definitions-editor-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.definitions-editor-input,
.definitions-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.definitions-editor-textarea {
    resize: vertical;
}
</style>
