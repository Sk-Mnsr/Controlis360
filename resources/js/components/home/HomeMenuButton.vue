<template>
    <button
        type="button"
        class="home-btn"
        :class="buttonClass"
        @click="$emit('click')"
    >
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'methodology', 'active', 'cartographie'].includes(v),
    },
});

defineEmits(['click']);

const buttonClass = computed(() => ({
    'home-btn-methodology': props.variant === 'methodology',
    'home-btn-active': props.variant === 'active',
    'home-btn-cartographie': props.variant === 'cartographie',
}));
</script>

<style scoped>
.home-btn {
    display: flex;
    width: 100%;
    min-height: 2.25rem;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.65);
    padding: 0.55rem 0.65rem;
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    line-height: 1.15;
    color: #1e293b;
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(6px);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease, color 0.15s ease;
}

.home-btn:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 0.9);
}

.home-btn:active {
    transform: scale(0.98);
}

.home-btn-methodology {
    justify-content: flex-start;
    color: #b91c1c;
    border-color: rgba(185, 28, 28, 0.35);
    background: rgba(255, 255, 255, 0.78);
}

.home-btn-methodology:hover {
    background: rgba(254, 242, 242, 0.92);
    border-color: rgba(185, 28, 28, 0.5);
}

.home-btn-active {
    color: #ffffff;
    border-color: #9b0000;
    background: linear-gradient(180deg, #dc2626 0%, #b91c1c 100%);
    box-shadow: 0 3px 10px rgba(185, 28, 28, 0.35);
}

.home-btn-active:hover {
    background: linear-gradient(180deg, #ef4444 0%, #c00000 100%);
}

.home-btn-cartographie {
    height: 100%;
    min-height: 0;
    border: none;
    border-radius: 0.75rem;
    background: transparent;
    box-shadow: none;
    padding: 0;
}

.home-btn-cartographie:hover {
    filter: brightness(1.05);
}

.home-btn-cartographie:active {
    transform: none;
}
</style>
