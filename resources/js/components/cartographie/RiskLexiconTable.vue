<template>
    <section class="risk-lexicon-section">
        <h3 class="risk-lexicon-title">Lexique</h3>

        <table class="risk-lexicon-table">
            <thead>
                <tr>
                    <th class="risk-lexicon-head">Niveau</th>
                    <th class="risk-lexicon-head">Description</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="item in sortedRows"
                    :key="item.id"
                    :class="{ 'risk-lexicon-row-highlight': item.code === 'non_significatif' }"
                >
                    <td class="risk-lexicon-level" :style="levelStyle(item)">{{ item.name }}</td>
                    <td class="risk-lexicon-description">{{ item.description }}</td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

const sortedRows = computed(() => [...props.rows].sort((a, b) => b.sort_order - a.sort_order));

function levelStyle(item) {
    if (!item.color) {
        return {};
    }

    const isLight = ['#fff176', '#81c784', '#ffb74d'].includes(item.color);

    return {
        backgroundColor: item.color,
        color: isLight ? '#111111' : '#ffffff',
    };
}
</script>

<style scoped>
.risk-lexicon-section {
    overflow-x: auto;
}

.risk-lexicon-title {
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #111111;
}

.risk-lexicon-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #111111;
}

.risk-lexicon-table th,
.risk-lexicon-table td {
    border: 1px solid #111111;
    padding: 0.55rem 0.65rem;
    vertical-align: top;
}

.risk-lexicon-head {
    background: #e5e7eb;
    font-weight: 700;
    text-align: center;
}

.risk-lexicon-level {
    width: 9rem;
    font-weight: 700;
    text-align: center;
}

.risk-lexicon-description {
    text-align: justify;
}

.risk-lexicon-row-highlight .risk-lexicon-description {
    background: #e8f5e9;
}
</style>
