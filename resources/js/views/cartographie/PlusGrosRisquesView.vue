<template>
    <div class="pgr-page">
        <header class="pgr-header">
            <div class="pgr-header-main">
                <RouterLink
                    :to="{ name: 'cartographie.cartographie', query: environmentQueryParams(route) }"
                    class="pgr-back"
                >
                    ← Cartographie
                </RouterLink>
                <div class="pgr-title-row">
                    <h1 class="pgr-title">Plus gros risques</h1>
                    <span v-if="!loading && !error" class="pgr-count">{{ rows.length }}</span>
                </div>
                <p class="pgr-hint">
                    Risques opérationnels à fort impact (Rb ≥ 20), triés par score décroissant.
                </p>
            </div>

            <label v-if="environmentOptions.length > 1" class="pgr-environment">
                <span>Environnement</span>
                <select
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
        </header>

        <div v-if="loading" class="pgr-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !rows.length" class="pgr-error">{{ error }}</p>
            <TopRisksTable v-else :title="title" :rows="rows" />
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useCartographieStore } from '../../stores/cartographie';
import { environmentQueryParams } from '../../utils/entityEnvironment';
import { uniqueEnvironments } from '../../utils/cartographyDashboard';
import TopRisksTable from '../../components/cartographie/TopRisksTable.vue';

const route = useRoute();
const router = useRouter();
const cartographie = useCartographieStore();

const loading = ref(true);
const error = ref('');
const title = ref('RISQUES OPERATIONNELS A FORT IMPACT BUSINESS');
const rows = ref([]);

const environmentOptions = computed(() =>
    uniqueEnvironments(cartographie.navigationEntities),
);

const selectedEnvironment = computed(() =>
    route.query.environment
        ?? environmentOptions.value[0]?.code
        ?? null,
);

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        title: root?.title ?? 'RISQUES OPERATIONNELS A FORT IMPACT BUSINESS',
        rows: root?.rows ?? [],
    };
}

async function loadTopRisques() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/top-risques', {
            params: environmentQueryParams(route),
        });
        const payload = extractPayload(data);
        title.value = payload.title;
        rows.value = payload.rows;
    } catch {
        error.value = 'Impossible de charger les plus gros risques.';
    } finally {
        loading.value = false;
    }
}

function changeEnvironment(event) {
    const environment = event.target.value;

    router.push({
        name: 'cartographie.plus-gros-risques',
        query: environment ? { environment } : {},
    });
}

watch(() => route.query.environment, loadTopRisques);

onMounted(loadTopRisques);
</script>

<style scoped>
.pgr-page {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 1.25rem 1.5rem 2rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    min-height: 100%;
    background:
        radial-gradient(ellipse 80% 50% at 100% 0%, rgba(192, 0, 0, 0.06), transparent 55%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.pgr-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.25rem;
    flex-wrap: wrap;
}

.pgr-header-main {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}

.pgr-back {
    width: fit-content;
    font-size: 0.8125rem;
    color: #64748b;
    transition: color 0.15s;
}

.pgr-back:hover {
    color: #0f172a;
}

.pgr-title-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.pgr-title {
    margin: 0;
    font-size: clamp(1.35rem, 2vw, 1.75rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
}

.pgr-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.75rem;
    height: 1.75rem;
    padding: 0 0.5rem;
    border-radius: 999px;
    background: #c00000;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
}

.pgr-hint {
    margin: 0;
    max-width: 42rem;
    font-size: 0.875rem;
    line-height: 1.45;
    color: #64748b;
}

.pgr-environment {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    min-width: 11rem;
}

.pgr-environment select {
    border: 1px solid #cbd5e1;
    border-radius: 0.55rem;
    padding: 0.5rem 0.7rem;
    font-size: 0.8125rem;
    color: #0f172a;
    background: #ffffff;
    text-transform: none;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.pgr-loading {
    padding: 3rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.pgr-error {
    border-radius: 0.65rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 0.85rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}
</style>
