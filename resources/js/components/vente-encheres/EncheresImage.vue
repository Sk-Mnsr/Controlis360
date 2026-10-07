<template>
    <img
        :src="currentSrc"
        :alt="alt"
        :loading="loading"
        @error="onError"
    />
</template>

<script setup>
import { ref, watch } from 'vue';
import { PLACEHOLDER_IMAGE } from '../../config/encheres-mock-data';

const props = defineProps({
    src: {
        type: String,
        default: '',
    },
    alt: {
        type: String,
        default: '',
    },
    loading: {
        type: String,
        default: 'lazy',
    },
});

const currentSrc = ref(props.src || PLACEHOLDER_IMAGE);
const failed = ref(false);

watch(() => props.src, (value) => {
    failed.value = false;
    currentSrc.value = value || PLACEHOLDER_IMAGE;
});

function onError() {
    if (failed.value) {
        return;
    }
    failed.value = true;
    currentSrc.value = PLACEHOLDER_IMAGE;
}
</script>
