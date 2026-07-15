<template>
    <div class="cartographie-page">
        <header class="cartographie-header">
            <div>
                <p class="cartographie-kicker">{{ subtitle }}</p>
                <h1 class="cartographie-title">{{ title }}</h1>
            </div>

            <div class="cartographie-header-actions">
                <p class="cartographie-date">{{ formattedDate }}</p>
                <label v-if="environmentOptions.length > 1" class="cartographie-environment">
                    <span class="cartographie-environment-label">Environnement</span>
                    <select
                        class="cartographie-environment-select"
                        :value="selectedEnvironment"
                        @change="changeEnvironment"
                    >
                        <option
                            v-for="environment in environmentOptions"
                            :key="environment.code"
                            :value="environment.code"
                        >
                            {{ environment.name || environment.code }}
                        </option>
                    </select>
                </label>
            </div>
        </header>

        <nav class="cartographie-tabs" aria-label="Type de cartographie">
            <button
                type="button"
                class="cartographie-tab"
                :class="{ active: activeTab === 'gross' }"
                @click="activeTab = 'gross'"
            >
                Cartographie des risques bruts
            </button>
            <button
                type="button"
                class="cartographie-tab"
                :class="{ active: activeTab === 'residual' }"
                @click="activeTab = 'residual'"
            >
                Cartographie des risques résiduels
            </button>
        </nav>

        <div v-if="loading" class="cartographie-loading">Chargement...</div>
        <p v-else-if="error" class="cartographie-error">{{ error }}</p>

        <CartographyDashboardPanel
            v-else
            :mode="activeTab"
            :mode-data="activeModeData"
            :matrix="matrix"
            :rows="rows"
            :categories="riskCategories"
            :classifications="classifications"
            :heatmap-title="activeTab === 'gross' ? 'Carte des risques bruts' : 'Carte des risques résiduels'"
            :probability-label="activeTab === 'gross' ? 'Probabilité' : 'Probabilité résiduelle'"
            :risk-label="activeTab === 'gross' ? 'Risque brut' : 'Risque résiduel'"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useCartographieStore } from '../../stores/cartographie';
import { environmentQueryParams } from '../../utils/entityEnvironment';
import { formatDashboardDate, uniqueEnvironments } from '../../utils/cartographyDashboard';
import CartographyDashboardPanel from '../../components/cartographie/CartographyDashboardPanel.vue';

const route = useRoute();
const router = useRouter();
const cartographie = useCartographieStore();

const loading = ref(true);
const error = ref('');
const activeTab = ref('gross');
const title = ref('CARTOGRAPHIE DES RISQUES');
const subtitle = ref('');
const matrix = ref([]);
const rows = ref([]);
const riskCategories = ref([]);
const classifications = ref([]);
const gross = ref({ entities: [], averages: null, distribution: [], total_entities: 0 });
const residual = ref({ entities: [], averages: null, distribution: [], total_entities: 0 });

const formattedDate = computed(() => formatDashboardDate());

const environmentOptions = computed(() =>
    uniqueEnvironments(cartographie.navigationEntities),
);

const selectedEnvironment = computed(() =>
    route.query.environment
        ?? environmentOptions.value[0]?.code
        ?? null,
);

const activeModeData = computed(() =>
    activeTab.value === 'residual' ? residual.value : gross.value,
);

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        title: root?.title ?? 'CARTOGRAPHIE DES RISQUES',
        subtitle: root?.subtitle ?? '',
        matrix: root?.matrix ?? [],
        rows: root?.rows ?? [],
        riskCategories: root?.risk_categories ?? [],
        classifications: root?.classifications ?? [],
        gross: root?.gross ?? { entities: [], averages: null, distribution: [], total_entities: 0 },
        residual: root?.residual ?? { entities: [], averages: null, distribution: [], total_entities: 0 },
    };
}

async function loadDashboard() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/cartographie-dashboard', {
            params: environmentQueryParams(route),
        });
        const payload = extractPayload(data);
        title.value = payload.title;
        subtitle.value = payload.subtitle;
        matrix.value = payload.matrix;
        rows.value = payload.rows;
        riskCategories.value = payload.riskCategories;
        classifications.value = payload.classifications;
        gross.value = payload.gross;
        residual.value = payload.residual;
        cartographie.resetDashboard();
    } catch {
        error.value = 'Impossible de charger la cartographie des risques.';
    } finally {
        loading.value = false;
    }
}

function changeEnvironment(event) {
    const environment = event.target.value;

    router.push({
        name: 'cartographie.cartographie',
        query: environment ? { environment } : {},
    });
}

watch(() => route.query.environment, loadDashboard);

onMounted(loadDashboard);
</script>

<style scoped>
.cartographie-page {
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding: 0 0.25rem;
}

.cartographie-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.cartographie-kicker {
    margin: 0;
    font-size: 0.8rem;
    color: #64748b;
}

.cartographie-title {
    margin: 0.25rem 0 0;
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: #0f172a;
}

.cartographie-header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.cartographie-date {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
}

.cartographie-environment {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.cartographie-environment-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
}

.cartographie-environment-select {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.45rem 0.65rem;
    font-size: 0.8125rem;
    color: #0f172a;
    background: #ffffff;
}

.cartographie-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.25rem;
}

.cartographie-tab {
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    padding: 0.55rem 0.9rem;
    background: transparent;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.cartographie-tab:hover {
    color: #0f172a;
}

.cartographie-tab.active {
    color: #0f172a;
    border-bottom-color: #c00000;
}

.cartographie-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.cartographie-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}
</style>
