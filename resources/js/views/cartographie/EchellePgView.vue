<template>
    <div class="echelle-pg-page">
        <div class="echelle-pg-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="echelle-pg-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="isSuperAdmin && !editing"
                type="button"
                class="echelle-pg-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="echelle-pg-loading">Chargement...</div>

        <template v-else>
            <header class="echelle-pg-title">
                Critère de quantification de la probabilité d'occurrence et de l'impact du risque opérationnel
            </header>

            <div v-if="!editing" class="echelle-pg-tables">
                <ScaleTable
                    title="Gravité"
                    level-label="Gravité"
                    variant="gravity"
                    :rows="gravity"
                />
                <ScaleTable
                    title="Probabilité"
                    level-label="Probabilité"
                    variant="probability"
                    :rows="probability"
                />
            </div>

            <form v-else class="echelle-pg-form" @submit.prevent="save">
                <ScaleTableEditor
                    title="Gravité"
                    level-label="Gravité"
                    variant="gravity"
                    :rows="form.gravity"
                    @add-row="addGravityRow"
                    @remove-row="removeGravityRow"
                />
                <ScaleTableEditor
                    title="Probabilité"
                    level-label="Probabilité"
                    variant="probability"
                    :rows="form.probability"
                    @add-row="addProbabilityRow"
                    @remove-row="removeProbabilityRow"
                />

                <p v-if="error" class="echelle-pg-error">{{ error }}</p>

                <div class="echelle-pg-form-actions">
                    <button type="button" class="echelle-pg-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="echelle-pg-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import ScaleTable from '../../components/cartographie/ScaleTable.vue';
import ScaleTableEditor from '../../components/cartographie/ScaleTableEditor.vue';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const gravity = ref([]);
const probability = ref([]);

const form = reactive({
    gravity: [],
    probability: [],
});

function extractPayload(data) {
    const root = data?.data ?? data;
    return {
        gravity: root?.gravity ?? [],
        probability: root?.probability ?? [],
    };
}

async function loadEchelle() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/echelle-pg');
        const payload = extractPayload(data);
        gravity.value = payload.gravity;
        probability.value = payload.probability;
    } catch {
        error.value = 'Impossible de charger l\'échelle P & G.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.gravity = gravity.value.map((row) => ({
        id: row.id,
        level: row.level,
        qualification: row.qualification ?? row.label ?? '',
        description: row.description ?? '',
    }));
    form.probability = probability.value.map((row) => ({
        id: row.id,
        level: row.level,
        qualification: row.qualification ?? row.label ?? '',
        description: row.description ?? '',
    }));
    editing.value = true;
    error.value = '';
}

function cancelEdit() {
    editing.value = false;
    error.value = '';
}

function nextLevel(rows) {
    const levels = rows.map((row) => Number(row.level) || 0);
    return levels.length ? Math.max(...levels) + 1 : 1;
}

function addGravityRow() {
    form.gravity.push({
        id: null,
        level: nextLevel(form.gravity),
        qualification: '',
        description: '',
    });
}

function removeGravityRow(index) {
    if (form.gravity.length <= 1) return;
    form.gravity.splice(index, 1);
}

function addProbabilityRow() {
    form.probability.push({
        id: null,
        level: nextLevel(form.probability),
        qualification: '',
        description: '',
    });
}

function removeProbabilityRow(index) {
    if (form.probability.length <= 1) return;
    form.probability.splice(index, 1);
}

async function save() {
    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.put('/referentials/echelle-pg', {
            gravity: form.gravity.map((row) => ({
                id: row.id,
                level: row.level,
                qualification: row.qualification,
                description: row.description,
            })),
            probability: form.probability.map((row) => ({
                id: row.id,
                level: row.level,
                qualification: row.qualification,
                description: row.description,
            })),
        });

        const payload = extractPayload(data);
        gravity.value = payload.gravity;
        probability.value = payload.probability;
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

onMounted(loadEchelle);
</script>

<style scoped>
.echelle-pg-page {
    max-width: 72rem;
    margin: 0 auto;
}

.echelle-pg-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.echelle-pg-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.echelle-pg-back:hover {
    color: #0f172a;
}

.echelle-pg-edit-btn,
.echelle-pg-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.echelle-pg-edit-btn:hover,
.echelle-pg-btn-primary:hover {
    opacity: 0.92;
}

.echelle-pg-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.echelle-pg-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.echelle-pg-title {
    margin-bottom: 1.5rem;
    background: #1e3a8a;
    padding: 0.85rem 1rem;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #ffffff;
}

.echelle-pg-tables,
.echelle-pg-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.echelle-pg-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.echelle-pg-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.echelle-pg-btn-secondary {
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
