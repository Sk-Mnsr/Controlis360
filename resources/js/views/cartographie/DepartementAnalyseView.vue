<template>
    <div class="departement-analyse-page">
        <div class="departement-analyse-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="departement-analyse-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="isSuperAdmin && !editing && entity"
                type="button"
                class="departement-analyse-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="departement-analyse-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !entity" class="departement-analyse-error">{{ error }}</p>

            <div v-else-if="!editing" class="departement-analyse-content">
                <OperationalRiskTable :title="title" :rows="rows" />
            </div>

            <form v-else class="departement-analyse-form" @submit.prevent="save">
                <OperationalRiskTableEditor
                    :rows="form.rows"
                    @add-row="addRow"
                    @remove-row="removeRow"
                />

                <p v-if="error" class="departement-analyse-error">{{ error }}</p>

                <div class="departement-analyse-form-actions">
                    <button type="button" class="departement-analyse-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="departement-analyse-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import { useCartographieStore } from '../../stores/cartographie';
import OperationalRiskTable from '../../components/cartographie/OperationalRiskTable.vue';
import OperationalRiskTableEditor from '../../components/cartographie/OperationalRiskTableEditor.vue';

const route = useRoute();
const auth = useAuthStore();
const cartographie = useCartographieStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const entity = ref(null);
const title = ref('');
const rows = ref([]);

const form = reactive({
    rows: [],
});

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        entity: root?.entity ?? null,
        title: root?.title ?? '',
        rows: root?.rows ?? [],
    };
}

async function loadAnalyse() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/referentials/analyse-risques/${route.params.code}`);
        const payload = extractPayload(data);
        entity.value = payload.entity;
        title.value = payload.title;
        rows.value = payload.rows;
        cartographie.selectedEntityCode = route.params.code;
    } catch {
        error.value = 'Impossible de charger l\'analyse des risques du département.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.rows = rows.value.map((row) => ({
        id: row.id,
        process_number: row.process_number,
        process_name: row.process_name ?? '',
        ratio: row.ratio,
        sub_process_name: row.sub_process_name ?? '',
        major_exceptions: row.major_exceptions ?? '',
        correlated_risks: row.correlated_risks ?? '',
        risk_family: row.risk_family ?? '',
        gravity: row.gravity,
        probability: row.probability,
        control_description: row.control_description ?? '',
        control_exists: row.control_exists,
        control_owner: row.control_owner ?? '',
        control_effectiveness: row.control_effectiveness,
        residual_gravity: row.residual_gravity,
        residual_probability: row.residual_probability,
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
        process_number: entity.value?.code === 'IT' ? 3 : null,
        process_name: entity.value?.name ?? '',
        ratio: null,
        sub_process_name: '',
        major_exceptions: '',
        correlated_risks: '',
        risk_family: '',
        gravity: null,
        probability: null,
        control_description: '',
        control_exists: null,
        control_owner: '',
        control_effectiveness: null,
        residual_gravity: null,
        residual_probability: null,
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
        const { data } = await api.put(`/referentials/analyse-risques/${route.params.code}`, {
            rows: form.rows.map((row) => ({
                id: row.id,
                process_number: row.process_number || null,
                process_name: row.process_name || null,
                ratio: row.ratio ?? null,
                sub_process_name: row.sub_process_name,
                major_exceptions: row.major_exceptions || null,
                correlated_risks: row.correlated_risks || null,
                risk_family: row.risk_family || null,
                gravity: row.gravity || null,
                probability: row.probability || null,
                control_description: row.control_description || null,
                control_exists: row.control_exists,
                control_owner: row.control_owner || null,
                control_effectiveness: row.control_effectiveness || null,
                residual_gravity: row.residual_gravity || null,
                residual_probability: row.residual_probability || null,
            })),
        });

        const payload = extractPayload(data);
        entity.value = payload.entity;
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

watch(() => route.params.code, loadAnalyse);

onMounted(loadAnalyse);
</script>

<style scoped>
.departement-analyse-page {
    max-width: 100%;
    margin: 0 auto;
}

.departement-analyse-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.departement-analyse-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.departement-analyse-back:hover {
    color: #0f172a;
}

.departement-analyse-edit-btn,
.departement-analyse-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.departement-analyse-edit-btn:hover,
.departement-analyse-btn-primary:hover {
    opacity: 0.92;
}

.departement-analyse-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.departement-analyse-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.departement-analyse-content,
.departement-analyse-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.departement-analyse-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.departement-analyse-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.departement-analyse-btn-secondary {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}
</style>
