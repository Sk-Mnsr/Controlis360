<template>
    <div class="methodology-grid-wrap">
        <table class="methodology-grid">
            <thead>
                <tr>
                    <th v-for="(column, index) in columns" :key="index">{{ column }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in rows" :key="index">
                    <td class="grid-label">{{ row.label }}</td>
                    <td>{{ row.statement }}</td>
                    <td>{{ row.explanation }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});
</script>

<style scoped>
.methodology-grid-wrap {
    overflow-x: auto;
}

.methodology-grid {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #111111;
    font-size: 0.875rem;
    line-height: 1.45;
}

.methodology-grid th,
.methodology-grid td {
    border: 1px solid #111111;
    padding: 0.65rem 0.75rem;
    vertical-align: top;
    text-align: left;
}

.methodology-grid thead th {
    background: #d1d5db;
    color: #111111;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.03em;
    text-align: center;
}

.methodology-grid .grid-label {
    width: 7rem;
    font-weight: 700;
    white-space: nowrap;
    text-align: center;
}

.methodology-grid td:nth-child(2) {
    width: 28%;
    font-weight: 600;
}

.methodology-grid td:nth-child(3) {
    width: auto;
    text-align: justify;
}
</style>
