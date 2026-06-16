<template>
    <div class="lexique-page">
        <div class="lexique-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="lexique-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="isSuperAdmin && !editing"
                type="button"
                class="lexique-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="lexique-loading">Chargement...</div>

        <template v-else>
            <header class="lexique-title">Lexique famille de risques</header>

            <p v-if="error && !categories.length" class="lexique-error">{{ error }}</p>

            <div v-else-if="!editing" class="lexique-content">
                <RiskFamiliesLexiconTable :categories="categories" />
            </div>

            <form v-else class="lexique-form" @submit.prevent="save">
                <RiskFamiliesLexiconEditor
                    :categories="form.categories"
                    @update="form.categories = $event"
                />

                <p v-if="error" class="lexique-error">{{ error }}</p>

                <div class="lexique-form-actions">
                    <button type="button" class="lexique-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="lexique-btn-primary" :disabled="saving">
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
import RiskFamiliesLexiconTable from '../../components/cartographie/RiskFamiliesLexiconTable.vue';
import RiskFamiliesLexiconEditor from '../../components/cartographie/RiskFamiliesLexiconEditor.vue';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const categories = ref([]);

const form = reactive({
    categories: [],
});

function extractPayload(data) {
    const root = data?.data ?? data;
    return root?.categories ?? root ?? [];
}

async function loadLexique() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/lexique-familles');
        categories.value = extractPayload(data);
    } catch {
        error.value = 'Impossible de charger le lexique des familles de risques.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.categories = categories.value.map((category) => ({
        id: category.id,
        number: category.number,
        name: category.name,
        description: category.description ?? '',
        families: (category.families ?? []).map((family) => ({
            id: family.id,
            name: family.name,
            definition: family.definition ?? '',
        })),
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
        const { data } = await api.put('/referentials/lexique-familles', {
            categories: form.categories.map((category) => ({
                id: category.id,
                number: category.number,
                name: category.name,
                description: category.description,
                families: category.families.map((family) => ({
                    id: family.id,
                    name: family.name,
                    definition: family.definition,
                })),
            })),
        });

        categories.value = extractPayload(data);
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

onMounted(loadLexique);
</script>

<style scoped>
.lexique-page {
    max-width: 80rem;
    margin: 0 auto;
}

.lexique-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.lexique-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.lexique-back:hover {
    color: #0f172a;
}

.lexique-edit-btn,
.lexique-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.lexique-edit-btn:hover,
.lexique-btn-primary:hover {
    opacity: 0.92;
}

.lexique-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.lexique-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.lexique-title {
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

.lexique-content,
.lexique-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.lexique-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.lexique-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.lexique-btn-secondary {
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
