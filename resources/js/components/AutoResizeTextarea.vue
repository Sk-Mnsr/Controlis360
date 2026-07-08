<template>
    <textarea
        ref="textareaRef"
        :value="modelValue"
        :rows="minRows"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        class="auto-resize-textarea w-full resize-none overflow-hidden rounded-lg border border-slate-300 px-3 py-2 text-sm leading-relaxed outline-none focus:border-emerald-500"
        @input="onInput"
    />
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    minRows: {
        type: Number,
        default: 2,
    },
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const textareaRef = ref(null);

function resize() {
    const element = textareaRef.value;
    if (!element) {
        return;
    }

    element.style.height = 'auto';
    element.style.height = `${element.scrollHeight}px`;
}

function onInput(event) {
    emit('update:modelValue', event.target.value);
    resize();
}

watch(() => props.modelValue, () => {
    nextTick(resize);
});

onMounted(() => {
    nextTick(resize);
});
</script>
