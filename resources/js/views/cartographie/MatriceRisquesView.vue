<template>
    <div class="matrice-risques-page">
        <div class="matrice-risques-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="matrice-risques-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="canEditMethodology && !editing"
                type="button"
                class="matrice-risques-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="matrice-risques-loading">Chargement...</div>

        <template v-else>
            <header class="matrice-risques-title">
                Matrice de criticité des risques opérationnels (G × P)
            </header>

            <div v-if="!editing" class="matrice-risques-content">
                <RiskMatrixGrid :matrix="matrix" :classifications="classifications" />
                <RiskLexiconTable id="lexique" :rows="classifications" />
            </div>

            <form v-else class="matrice-risques-form" @submit.prevent="save">
                <RiskMatrixGrid :matrix="matrix" :classifications="classifications" />

                <p class="matrice-risques-note">
                    La grille G × P est fixe. Seul le lexique est modifiable.
                </p>

                <RiskLexiconEditor :rows="form.classifications" />

                <p v-if="error" class="matrice-risques-error">{{ error }}</p>

                <div class="matrice-risques-form-actions">
                    <button type="button" class="matrice-risques-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="matrice-risques-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../../api/client';
import { useCartographiePermissions } from '../../composables/useCartographiePermissions';
import RiskMatrixGrid from '../../components/cartographie/RiskMatrixGrid.vue';
import RiskLexiconTable from '../../components/cartographie/RiskLexiconTable.vue';
import RiskLexiconEditor from '../../components/cartographie/RiskLexiconEditor.vue';

const { canEditMethodology } = useCartographiePermissions();

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const matrix = ref([]);
const classifications = ref([]);

const form = reactive({
    classifications: [],
});

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        matrix: root?.matrix ?? [],
        classifications: root?.classifications ?? [],
    };
}

async function loadMatrice() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/matrice-risques');
        const payload = extractPayload(data);
        matrix.value = payload.matrix;
        classifications.value = payload.classifications;
    } catch {
        error.value = 'Impossible de charger la matrice des risques.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.classifications = classifications.value.map((row) => ({
        id: row.id,
        name: row.name,
        description: row.description ?? '',
        color: row.color,
        sort_order: row.sort_order,
        code: row.code,
    }));
    editing.value = true;
    error.value = '';
}

function cancelEdit() {
    editing.value = false;
    error.value = '';
}

async function save() {
    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.put('/referentials/matrice-risques', {
            classifications: form.classifications.map((row) => ({
                id: row.id,
                name: row.name,
                description: row.description,
            })),
        });

        const payload = extractPayload(data);
        matrix.value = payload.matrix;
        classifications.value = payload.classifications;
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

onMounted(loadMatrice);
</script>

<style scoped>
.matrice-risques-page {
    max-width: 72rem;
    margin: 0 auto;
}

.matrice-risques-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.matrice-risques-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.matrice-risques-back:hover {
    color: #0f172a;
}

.matrice-risques-edit-btn,
.matrice-risques-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.matrice-risques-edit-btn:hover,
.matrice-risques-btn-primary:hover {
    opacity: 0.92;
}

.matrice-risques-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.matrice-risques-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.matrice-risques-title {
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

.matrice-risques-content,
.matrice-risques-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.matrice-risques-note {
    font-size: 0.8125rem;
    color: #64748b;
}

.matrice-risques-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.matrice-risques-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.matrice-risques-btn-secondary {
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
