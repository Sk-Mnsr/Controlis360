<template>
    <section class="risk-lexicon-editor">
        <h3 class="risk-lexicon-editor-title">Lexique</h3>

        <div v-for="row in sortedRows" :key="row.id" class="risk-lexicon-editor-row">
            <div class="risk-lexicon-editor-level">
                <label class="risk-lexicon-editor-label">Niveau</label>
                <input v-model="row.name" required class="risk-lexicon-editor-input" />
            </div>

            <div class="risk-lexicon-editor-field">
                <label class="risk-lexicon-editor-label">Description</label>
                <textarea v-model="row.description" required rows="3" class="risk-lexicon-editor-textarea" />
            </div>

            <span class="risk-lexicon-editor-badge" :style="{ backgroundColor: row.color }" />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, required: true },
});

const sortedRows = computed(() => [...props.rows].sort((a, b) => b.sort_order - a.sort_order));
</script>

<style scoped>
.risk-lexicon-editor {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.risk-lexicon-editor-title {
    font-size: 0.95rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #111111;
}

.risk-lexicon-editor-row {
    display: grid;
    gap: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 1rem;
}

@media (min-width: 900px) {
    .risk-lexicon-editor-row {
        grid-template-columns: 11rem 1fr auto;
        align-items: start;
    }
}

.risk-lexicon-editor-level,
.risk-lexicon-editor-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.risk-lexicon-editor-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.risk-lexicon-editor-input,
.risk-lexicon-editor-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.risk-lexicon-editor-textarea {
    resize: vertical;
}

.risk-lexicon-editor-badge {
    display: inline-block;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 9999px;
}
</style>
