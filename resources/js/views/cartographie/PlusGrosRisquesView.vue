<template>
    <div class="plus-gros-risques-page">
        <div class="plus-gros-risques-actions">
<<<<<<< HEAD
            <RouterLink :to="{ name: 'cartographie.home' }" class="plus-gros-risques-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="canEditMethodology && !editing"
                type="button"
                class="plus-gros-risques-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
=======
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
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
        </div>

        <div v-if="loading" class="plus-gros-risques-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !rows.length" class="plus-gros-risques-error">{{ error }}</p>

<<<<<<< HEAD
            <div v-else-if="!editing" class="plus-gros-risques-content">
                <TopRisksTable :title="title" :rows="rows" />
            </div>

            <form v-else class="plus-gros-risques-form" @submit.prevent="save">
                <TopRisksTableEditor
                    :title="title"
                    :rows="form.rows"
                    @add-row="addRow"
                    @remove-row="removeRow"
                />

                <p v-if="error" class="plus-gros-risques-error">{{ error }}</p>

                <div class="plus-gros-risques-form-actions">
                    <button type="button" class="plus-gros-risques-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="plus-gros-risques-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
=======
            <div v-else class="plus-gros-risques-content">
                <p class="plus-gros-risques-hint">
                    Risques opérationnels à fort impact (Rb ≥ 10), triés par score décroissant.
                </p>
                <TopRisksTable :title="title" :rows="rows" />
            </div>
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
        </template>
    </div>
</template>

<script setup>
<<<<<<< HEAD
import { onMounted, reactive, ref } from 'vue';
import api from '../../api/client';
import { useCartographiePermissions } from '../../composables/useCartographiePermissions';
import TopRisksTable from '../../components/cartographie/TopRisksTable.vue';
import TopRisksTableEditor from '../../components/cartographie/TopRisksTableEditor.vue';

const { canEditMethodology } = useCartographiePermissions();

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
=======
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
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
const error = ref('');
const title = ref('RISQUES OPERATIONNELS A FORT IMPACT BUSINESS');
const rows = ref([]);

<<<<<<< HEAD
const form = reactive({
    rows: [],
});
=======
const environmentOptions = computed(() =>
    uniqueEnvironments(cartographie.navigationEntities),
);

const selectedEnvironment = computed(() =>
    route.query.environment
        ?? environmentOptions.value[0]?.code
        ?? null,
);
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec

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
<<<<<<< HEAD
        const { data } = await api.get('/referentials/top-risques');
=======
        const { data } = await api.get('/referentials/top-risques', {
            params: environmentQueryParams(route),
        });
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
        const payload = extractPayload(data);
        title.value = payload.title;
        rows.value = payload.rows;
    } catch {
        error.value = 'Impossible de charger les plus gros risques.';
    } finally {
        loading.value = false;
    }
}

<<<<<<< HEAD
function startEdit() {
    form.rows = rows.value.map((row) => ({
        id: row.id,
        process_name: row.process_name ?? '',
        sub_process_name: row.sub_process_name ?? '',
        major_exceptions: row.major_exceptions ?? '',
        risk_family: row.risk_family ?? '',
        gravity: row.gravity,
        probability: row.probability,
    }));
    editing.value = true;
    error.value = '';
}

function cancelEdit() {
    editing.value = false;
    error.value = '';
}

function addRow() {
    form.rows.push({
        id: null,
        process_name: '',
        sub_process_name: '',
        major_exceptions: '',
        risk_family: '',
        gravity: null,
        probability: null,
    });
}

function removeRow(index) {
    if (form.rows.length <= 1) return;
    form.rows.splice(index, 1);
}

async function save() {
    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.put('/referentials/top-risques', {
            rows: form.rows.map((row) => ({
                id: row.id,
                process_name: row.process_name || null,
                sub_process_name: row.sub_process_name,
                major_exceptions: row.major_exceptions || null,
                risk_family: row.risk_family || null,
                gravity: row.gravity || null,
                probability: row.probability || null,
            })),
        });

        const payload = extractPayload(data);
        title.value = payload.title;
        rows.value = payload.rows;
        editing.value = false;
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}
=======
function changeEnvironment(event) {
    const environment = event.target.value;

    router.push({
        name: 'cartographie.plus-gros-risques',
        query: environment ? { environment } : {},
    });
}

watch(() => route.query.environment, loadTopRisques);
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec

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
<<<<<<< HEAD
=======
    flex-wrap: wrap;
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
}

.plus-gros-risques-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.plus-gros-risques-back:hover {
    color: #0f172a;
}

<<<<<<< HEAD
.plus-gros-risques-edit-btn,
.plus-gros-risques-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.plus-gros-risques-edit-btn:hover,
.plus-gros-risques-btn-primary:hover {
    opacity: 0.92;
}

.plus-gros-risques-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
=======
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
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
}

.plus-gros-risques-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

<<<<<<< HEAD
.plus-gros-risques-content,
.plus-gros-risques-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
=======
.plus-gros-risques-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.plus-gros-risques-hint {
    margin: 0;
    font-size: 0.8125rem;
    color: #64748b;
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
}

.plus-gros-risques-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}
<<<<<<< HEAD

.plus-gros-risques-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.plus-gros-risques-btn-secondary {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}
=======
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
</style>
