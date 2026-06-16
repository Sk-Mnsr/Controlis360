<template>
    <section class="control-scale-section">
        <table class="control-scale-table">
            <thead>
                <tr>
                    <th colspan="4" class="control-scale-group">Echelle de contrôle</th>
                </tr>
                <tr>
                    <th class="control-scale-head">Niveau de l'échelle</th>
                    <th class="control-scale-head">Qualification de l'échelle</th>
                    <th class="control-scale-head">Explicitation de l'échelle</th>
                    <th class="control-scale-head control-scale-head-maturity">Maturité du contrôle</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td class="control-scale-level">{{ row.level }}</td>
                    <td class="control-scale-qualification">{{ row.qualification ?? row.label }}</td>
                    <td class="control-scale-description">{{ row.description }}</td>
                    <td class="control-scale-maturity">{{ row.maturity_label }}</td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
defineProps({
    rows: { type: Array, default: () => [] },
});
</script>

<style scoped>
.control-scale-section {
    overflow-x: auto;
}

.control-scale-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #111111;
}

.control-scale-table th,
.control-scale-table td {
    border: 1px solid #111111;
    padding: 0.55rem 0.65rem;
    vertical-align: top;
}

.control-scale-group {
    background: #d1d5db;
    text-align: center;
    font-weight: 700;
    text-transform: uppercase;
}

.control-scale-head {
    background: #e5e7eb;
    font-weight: 700;
    text-align: center;
}

.control-scale-head-maturity {
    background: #c8e6c9;
}

.control-scale-level {
    width: 5rem;
    text-align: center;
    font-weight: 700;
}

.control-scale-qualification {
    width: 10rem;
    font-weight: 600;
}

.control-scale-description {
    text-align: justify;
}

.control-scale-maturity {
    width: 9rem;
    background: #e8f5e9;
    font-weight: 600;
    text-align: center;
}
</style>
