<template>
    <div class="plus-gros-risques-page">
        <div class="plus-gros-risques-actions">
            <RouterLink :to="{ name: 'cartographie.cartographie', query: environmentQueryParams(route) }" class="plus-gros-risques-back">
                ← Cartographie
            </RouterLink>

            <label v-if="environmentOptions.length > 1" class="plus-gros-risques-environment">
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
        </div>

        <div v-if="loading" class="plus-gros-risques-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !rows.length" class="plus-gros-risques-error">{{ error }}</p>

            <div v-else class="plus-gros-risques-content">
                <p class="plus-gros-risques-hint">
                    Risques opérationnels à fort impact (Rb ≥ 10), triés par score décroissant.
                </p>
                <TopRisksTable :title="title" :rows="rows" />
            </div>
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
.plus-gros-risques-page {
    max-width: 80rem;
    margin: 0 auto;
}

.plus-gros-risques-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.plus-gros-risques-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.plus-gros-risques-back:hover {
    color: #0f172a;
}

.plus-gros-risques-environment {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
}

.plus-gros-risques-environment select {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.45rem 0.65rem;
    font-size: 0.8125rem;
    color: #0f172a;
    background: #ffffff;
    text-transform: none;
    font-weight: 500;
}

.plus-gros-risques-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.plus-gros-risques-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.plus-gros-risques-hint {
    margin: 0;
    font-size: 0.8125rem;
    color: #64748b;
}

.plus-gros-risques-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}
</style>
