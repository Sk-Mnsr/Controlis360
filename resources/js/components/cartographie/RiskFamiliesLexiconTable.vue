<template>
    <section class="risk-families-lexicon">
        <table class="risk-families-table">
            <thead>
                <tr>
                    <th class="risk-families-head">N°</th>
                    <th class="risk-families-head">Famille de risques</th>
                    <th class="risk-families-head">Détails par famille</th>
                    <th class="risk-families-head">Définition</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="category in categories" :key="category.id">
                    <tr v-for="(family, index) in category.families" :key="family.id">
                        <td
                            v-if="index === 0"
                            class="risk-families-number"
                            :rowspan="category.families.length"
                        >
                            {{ category.number }}
                        </td>
                        <td
                            v-if="index === 0"
                            class="risk-families-category"
                            :rowspan="category.families.length"
                        >
                            <strong>{{ category.name }}</strong>
                            <p v-if="category.description" class="risk-families-category-desc">
                                {{ category.description }}
                            </p>
                        </td>
                        <td class="risk-families-detail">{{ family.name }}</td>
                        <td class="risk-families-definition">{{ family.definition || '—' }}</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </section>
</template>

<script setup>
defineProps({
    categories: { type: Array, default: () => [] },
});
</script>

<style scoped>
.risk-families-lexicon {
    overflow-x: auto;
}

.risk-families-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #111111;
}

.risk-families-table th,
.risk-families-table td {
    border: 1px solid #111111;
    padding: 0.55rem 0.65rem;
    vertical-align: top;
}

.risk-families-head {
    background: #d1d5db;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    font-size: 0.75rem;
}

.risk-families-number {
    width: 3rem;
    font-weight: 700;
    text-align: center;
}

.risk-families-category {
    width: 22%;
    font-weight: 600;
}

.risk-families-category-desc {
    margin-top: 0.5rem;
    font-weight: 400;
    text-align: justify;
}

.risk-families-detail {
    width: 22%;
    font-weight: 600;
}

.risk-families-definition {
    text-align: justify;
}
</style>
