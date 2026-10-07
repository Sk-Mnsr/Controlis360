<template>
    <span class="ench-countdown" :class="{ 'ench-countdown--ended': countdown.ended }">
        <span v-if="!countdown.ended" class="ench-countdown-icon" aria-hidden="true">⏱</span>
        {{ display }}
    </span>
</template>

<script setup>
import { computed, toRef } from 'vue';
import { useEncheresCountdown } from '../../composables/useEncheresCountdown';

const props = defineProps({
    endsAt: {
        type: String,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const { countdown, label, shortLabel } = useEncheresCountdown(toRef(props, 'endsAt'));

const display = computed(() => (props.compact ? shortLabel.value : label.value));
</script>

<style scoped>
.ench-countdown {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #0f172a;
}

.ench-countdown--ended {
    color: #64748b;
}

.ench-countdown-icon {
    font-size: 0.9rem;
}
</style>
