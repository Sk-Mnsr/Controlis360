<template>
    <div class="principes-page">
        <div class="principes-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="principes-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="canEditMethodology && !editing"
                type="button"
                class="principes-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="principes-loading">Chargement...</div>

        <template v-else>
            <header class="principes-title">{{ page?.title ?? 'Principes' }}</header>

            <p v-if="error && !page" class="principes-error">{{ error }}</p>

            <div v-else-if="!editing" class="principes-content">
                <MethodologyGrid
                    v-if="page?.grid_data"
                    :columns="page.grid_data.columns"
                    :rows="page.grid_data.rows"
                />
            </div>

            <form v-else class="principes-form" @submit.prevent="save">
                <div>
                    <label class="principes-label">Titre</label>
                    <input v-model="form.title" required class="principes-input" />
                </div>

                <MethodologyGridEditor
                    :columns="form.grid_data.columns"
                    :rows="form.grid_data.rows"
                    @update="form.grid_data = $event"
                />

                <p v-if="error" class="principes-error">{{ error }}</p>

                <div class="principes-form-actions">
                    <button type="button" class="principes-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="principes-btn-primary" :disabled="saving">
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
import MethodologyGrid from '../../components/methodology/MethodologyGrid.vue';
import MethodologyGridEditor from '../../components/methodology/MethodologyGridEditor.vue';

const { canEditMethodology } = useCartographiePermissions();

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const page = ref(null);

const form = reactive({
    title: '',
    grid_data: { columns: [], rows: [] },
});

function extractPage(payload) {
    const data = payload?.data ?? payload;
    return data?.methodology_page ?? data;
}

async function loadPage() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/methodology-pages/principes');
        page.value = extractPage(data);
    } catch {
        error.value = 'Impossible de charger les principes.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.title = page.value.title;
    form.grid_data = JSON.parse(JSON.stringify(page.value.grid_data ?? { columns: [], rows: [] }));
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
        const { data } = await api.put('/methodology-pages/principes', {
            title: form.title,
            grid_data: form.grid_data,
        });

        page.value = extractPage(data);
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

onMounted(loadPage);
</script>

<style scoped>
.principes-page {
    max-width: 72rem;
    margin: 0 auto;
}

.principes-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.principes-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.principes-back:hover {
    color: #0f172a;
}

.principes-edit-btn,
.principes-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.principes-edit-btn:hover,
.principes-btn-primary:hover {
    opacity: 0.92;
}

.principes-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.principes-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.principes-title {
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

.principes-content,
.principes-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.principes-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.principes-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.principes-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.principes-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.principes-btn-secondary {
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
