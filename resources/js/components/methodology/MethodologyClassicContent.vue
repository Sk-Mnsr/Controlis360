<template>
    <section class="definitions-classic">
        <p v-if="introduction" class="definitions-intro">{{ introduction }}</p>

        <section v-for="(section, index) in sections" :key="index" class="definitions-section">
            <h2 class="definitions-section-title">{{ section.title }}</h2>
            <p v-if="section.content" class="definitions-paragraph">{{ section.content }}</p>
            <p v-if="section.subtitle" class="definitions-subtitle">{{ section.subtitle }}</p>
            <ul v-if="section.items?.length" class="definitions-list">
                <li v-for="(item, itemIndex) in section.items" :key="itemIndex">{{ item }}</li>
            </ul>
        </section>

        <p v-if="conclusion" class="definitions-conclusion">{{ conclusion }}</p>
    </section>
</template>

<script setup>
defineProps({
    introduction: { type: String, default: '' },
    sections: { type: Array, default: () => [] },
    conclusion: { type: String, default: '' },
});
</script>

<style scoped>
.definitions-classic {
    font-size: 0.95rem;
    line-height: 1.55;
    color: #111111;
}

.definitions-intro,
.definitions-paragraph,
.definitions-conclusion {
    margin-bottom: 1.25rem;
    text-align: justify;
}

.definitions-section {
    margin-bottom: 1.5rem;
}

.definitions-section-title {
    margin-bottom: 0.75rem;
    font-size: 1rem;
    font-weight: 700;
    font-style: italic;
}

.definitions-subtitle {
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.definitions-list {
    margin: 0.5rem 0 0;
    padding: 0;
    list-style: none;
}

.definitions-list li {
    margin-bottom: 0.35rem;
    padding-left: 0.25rem;
}

.definitions-list li::before {
    content: '- ';
}
</style>
