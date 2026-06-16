<template>
    <div class="preambule-content" v-html="html" />
</template>

<script setup>
defineProps({
    html: { type: String, default: '' },
});
</script>

<style>
.preambule-content p {
    margin-bottom: 1rem;
    text-align: justify;
}

.preambule-content .mp-red {
    color: #c00000;
    font-weight: 700;
}

.preambule-content .mp-blue,
.preambule-content a.mp-blue {
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.preambule-content a.mp-blue:hover {
    text-decoration: underline;
}

.preambule-content .mp-green {
    color: #15803d;
}

.preambule-content .mp-bold {
    font-weight: 700;
}

.preambule-content .mp-section-title {
    margin: 1.5rem 0 1rem;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: underline;
    color: #c00000;
}

.preambule-content .mp-step-title {
    margin: 1.25rem 0 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: underline;
}

.preambule-content .mp-footer {
    margin-top: 2rem;
    font-size: 1.15rem;
    font-weight: 700;
    text-align: center;
    color: #c00000;
}
</style>
