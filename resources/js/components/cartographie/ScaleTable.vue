<template>
    <section class="scale-table-section">
        <table class="scale-table">
            <thead>
                <tr>
                    <th :colspan="3" class="scale-table-group" :class="`scale-table-group-${variant}`">
                        {{ title }}
                    </th>
                </tr>
                <tr>
                    <th class="scale-table-head" :class="`scale-table-head-${variant}`">{{ levelLabel }}</th>
                    <th class="scale-table-head" :class="`scale-table-head-${variant}`">Qualification de l'échelle</th>
                    <th class="scale-table-head" :class="`scale-table-head-${variant}`">
                        Explication de l'échelle de {{ variant === 'gravity' ? 'gravité' : 'probabilité' }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td class="scale-table-level">{{ row.level }}</td>
                    <td class="scale-table-qualification">{{ row.qualification ?? row.label }}</td>
                    <td class="scale-table-description">{{ row.description }}</td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    levelLabel: { type: String, required: true },
    variant: { type: String, required: true },
    rows: { type: Array, default: () => [] },
});
</script>

<style scoped>
.scale-table-section {
    overflow-x: auto;
}

.scale-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #111111;
}

.scale-table th,
.scale-table td {
    border: 1px solid #111111;
    padding: 0.55rem 0.65rem;
    vertical-align: top;
}

.scale-table-group {
    text-align: center;
    font-weight: 700;
    text-transform: uppercase;
}

.scale-table-group-gravity {
    background: #f4a460;
}

.scale-table-group-probability {
    background: #f5e6a3;
}

.scale-table-head {
    font-weight: 700;
    text-align: center;
}

.scale-table-head-gravity {
    background: #f8c9a0;
}

.scale-table-head-probability {
    background: #faf0c8;
}

.scale-table-level {
    width: 4.5rem;
    text-align: center;
    font-weight: 700;
}

.scale-table-qualification {
    width: 16rem;
    font-weight: 600;
}

.scale-table-description {
    text-align: justify;
}
</style>
