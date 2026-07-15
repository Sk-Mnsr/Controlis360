<template>
    <div class="cartography-dashboard-panel">
        <RiskCategoryKpiCards
            :rows="rows"
            :categories="categories"
            :classifications="classifications"
            :mode="mode"
        />

        <div class="cartography-dashboard-main">
            <CartographyHeatmap
                class="cartography-dashboard-heatmap"
                :title="heatmapTitle"
                :matrix="matrix"
                :entities="modeData.entities"
                :classifications="classifications"
                :empty-message="heatmapEmptyMessage"
            />

            <div class="cartography-dashboard-side">
                <RiskDetailSummary
                    :rows="rows"
                    :categories="categories"
                    :classifications="classifications"
                    :mode="mode"
                />
                <RiskDistributionDonut
                    :distribution="modeData.distribution"
                    :classifications="classifications"
                    :total-entities="modeData.total_entities"
                />
            </div>
        </div>

        <CartographyEntityTable
            :entities="modeData.entities"
            :averages="modeData.averages"
            :probability-label="probabilityLabel"
            :risk-label="riskLabel"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import RiskCategoryKpiCards from './RiskCategoryKpiCards.vue';
import CartographyHeatmap from './CartographyHeatmap.vue';
import RiskDetailSummary from './RiskDetailSummary.vue';
import RiskDistributionDonut from './RiskDistributionDonut.vue';
import CartographyEntityTable from './CartographyEntityTable.vue';

const props = defineProps({
    mode: { type: String, required: true },
    modeData: { type: Object, required: true },
    matrix: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
    heatmapTitle: { type: String, default: 'Carte des risques' },
    probabilityLabel: { type: String, default: 'Probabilité' },
    riskLabel: { type: String, default: 'Risque brut' },
});

const heatmapEmptyMessage = computed(() => {
    if (props.mode !== 'residual') {
        return 'Aucune entité évaluée pour cet environnement.';
    }

    return 'Aucune entité positionnée — renseignez les dispositifs de contrôle (phase 2) pour alimenter la cartographie résiduelle.';
});
</script>

<style scoped>
.cartography-dashboard-panel {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.cartography-dashboard-main {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(18rem, 0.8fr);
    gap: 1rem;
    align-items: start;
}

.cartography-dashboard-side {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (max-width: 1100px) {
    .cartography-dashboard-main {
        grid-template-columns: 1fr;
    }
}
</style>
