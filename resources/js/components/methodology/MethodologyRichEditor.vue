<template>
    <div class="rich-editor">
        <div class="rich-editor-toolbar">
            <div class="rich-editor-modes">
                <button
                    type="button"
                    class="rich-editor-mode"
                    :class="{ active: mode === 'visual' }"
                    @click="mode = 'visual'"
                >
                    Éditeur
                </button>
                <button
                    type="button"
                    class="rich-editor-mode"
                    :class="{ active: mode === 'source' }"
                    @click="switchToSource"
                >
                    Code HTML
                </button>
            </div>

            <div v-if="mode === 'visual'" class="rich-editor-actions">
                <button type="button" class="rich-editor-btn" title="Gras" @click="formatBold">G</button>
                <button type="button" class="rich-editor-btn accent-red" title="Rouge COFINA" @click="wrapClass('mp-red')">A</button>
                <button type="button" class="rich-editor-btn accent-blue" title="Bleu" @click="wrapClass('mp-blue')">A</button>
                <button type="button" class="rich-editor-btn accent-green" title="Vert" @click="wrapClass('mp-green')">A</button>
                <button type="button" class="rich-editor-btn" title="Titre de section" @click="wrapBlock('h2', 'mp-section-title')">Titre</button>
                <button type="button" class="rich-editor-btn" title="Titre d’étape" @click="wrapBlock('h3', 'mp-step-title')">Étape</button>
                <button type="button" class="rich-editor-btn" title="Paragraphe" @click="formatParagraph">¶</button>
                <button type="button" class="rich-editor-btn" title="Liste" @click="formatList">• Liste</button>
            </div>
        </div>

        <div
            v-show="mode === 'visual'"
            ref="visualEl"
            class="rich-editor-visual preambule-content"
            contenteditable="true"
            @input="onVisualInput"
            @blur="syncFromVisual"
        />

        <textarea
            v-show="mode === 'source'"
            v-model="sourceValue"
            class="rich-editor-source"
            :rows="rows"
            @input="onSourceInput"
        />

        <p class="rich-editor-hint">
            Mode éditeur : mise en forme visuelle. Mode code HTML : pour les ajustements avancés.
        </p>
    </div>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

const model = defineModel({ type: String, default: '' });

defineProps({
    rows: { type: Number, default: 22 },
});

const mode = ref('visual');
const visualEl = ref(null);
const sourceValue = ref('');
let syncing = false;

function setVisualHtml(html) {
    if (!visualEl.value) return;
    syncing = true;
    visualEl.value.innerHTML = html || '<p></p>';
    syncing = false;
}

function syncFromVisual() {
    if (!visualEl.value) return;
    model.value = visualEl.value.innerHTML;
    sourceValue.value = model.value;
}

function onVisualInput() {
    if (syncing) return;
    syncFromVisual();
}

function onSourceInput() {
    model.value = sourceValue.value;
}

function switchToSource() {
    syncFromVisual();
    sourceValue.value = model.value ?? '';
    mode.value = 'source';
}

function focusVisual() {
    visualEl.value?.focus();
}

function formatBold() {
    focusVisual();
    document.execCommand('bold');
    syncFromVisual();
}

function formatParagraph() {
    focusVisual();
    document.execCommand('formatBlock', false, 'p');
    syncFromVisual();
}

function formatList() {
    focusVisual();
    document.execCommand('insertUnorderedList');
    syncFromVisual();
}

function wrapClass(className) {
    focusVisual();
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
        return;
    }

    const range = selection.getRangeAt(0);
    const span = document.createElement('span');
    span.className = className;
    span.appendChild(range.extractContents());
    range.insertNode(span);
    selection.removeAllRanges();
    selection.addRange(range);
    syncFromVisual();
}

function wrapBlock(tagName, className) {
    focusVisual();
    document.execCommand('formatBlock', false, tagName);
    const selection = window.getSelection();
    const node = selection?.anchorNode;
    const element = node?.nodeType === Node.ELEMENT_NODE ? node : node?.parentElement;
    const block = element?.closest(tagName);
    if (block) {
        block.className = className;
    }
    syncFromVisual();
}

watch(() => model.value, async (value) => {
    if (mode.value === 'source') {
        if (sourceValue.value !== value) {
            sourceValue.value = value ?? '';
        }
        return;
    }

    if (!visualEl.value) return;
    if (visualEl.value.innerHTML === value) return;

    await nextTick();
    setVisualHtml(value);
});

onMounted(async () => {
    sourceValue.value = model.value ?? '';
    await nextTick();
    setVisualHtml(model.value);
});
</script>

<style scoped>
.rich-editor {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.rich-editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
}

.rich-editor-modes,
.rich-editor-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.rich-editor-mode,
.rich-editor-btn {
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    background: #fff;
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
}

.rich-editor-mode.active {
    border-color: #c00000;
    background: #fef2f2;
    color: #c00000;
}

.rich-editor-btn:hover,
.rich-editor-mode:hover {
    background: #f8fafc;
}

.rich-editor-btn.accent-red {
    color: #c00000;
}

.rich-editor-btn.accent-blue {
    color: #2563eb;
}

.rich-editor-btn.accent-green {
    color: #15803d;
}

.rich-editor-visual {
    min-height: 22rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.65rem;
    padding: 1rem 1.1rem;
    background: #fff;
    overflow: auto;
}

.rich-editor-visual:focus {
    outline: none;
    border-color: #c00000;
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.1);
}

.rich-editor-source {
    width: 100%;
    min-height: 22rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.65rem;
    padding: 0.85rem 1rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    line-height: 1.45;
    color: #0f172a;
    resize: vertical;
}

.rich-editor-source:focus {
    outline: none;
    border-color: #c00000;
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.1);
}

.rich-editor-hint {
    margin: 0;
    font-size: 0.75rem;
    color: #64748b;
}
</style>

<style>
/* Styles partagés avec l’aperçu méthodologie */
.rich-editor-visual.preambule-content p {
    margin-bottom: 1rem;
    text-align: justify;
}

.rich-editor-visual.preambule-content .mp-red {
    color: #c00000;
    font-weight: 700;
}

.rich-editor-visual.preambule-content .mp-blue,
.rich-editor-visual.preambule-content a.mp-blue {
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.rich-editor-visual.preambule-content .mp-green {
    color: #15803d;
}

.rich-editor-visual.preambule-content .mp-bold {
    font-weight: 700;
}

.rich-editor-visual.preambule-content .mp-section-title {
    margin: 1.5rem 0 1rem;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: underline;
    color: #c00000;
}

.rich-editor-visual.preambule-content .mp-step-title {
    margin: 1.25rem 0 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: underline;
}

.rich-editor-visual.preambule-content .mp-footer {
    margin-top: 2rem;
    font-size: 1.15rem;
    font-weight: 700;
    text-align: center;
    color: #c00000;
}
</style>
