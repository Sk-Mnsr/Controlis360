<template>
    <div class="definitions-page">
        <div class="definitions-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="definitions-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="isSuperAdmin && !editing"
                type="button"
                class="definitions-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="definitions-loading">Chargement...</div>

        <template v-else>
            <header class="definitions-title">{{ page?.title ?? 'Définitions & Objectifs' }}</header>

            <p v-if="error && !page" class="definitions-error">{{ error }}</p>

            <div v-else-if="!editing" class="definitions-content">
                <MethodologyClassicContent
                    :introduction="page.introduction"
                    :sections="page.sections"
                    :conclusion="page.conclusion"
                />
            </div>

            <form v-else class="definitions-form" @submit.prevent="save">
                <div>
                    <label class="definitions-label">Titre</label>
                    <input v-model="form.title" required class="definitions-input" />
                </div>

                <MethodologyClassicEditor
                    :introduction="form.introduction"
                    :sections="form.sections"
                    :conclusion="form.conclusion"
                    @update="updateClassicContent"
                />

                <p v-if="error" class="definitions-error">{{ error }}</p>

                <div class="definitions-form-actions">
                    <button type="button" class="definitions-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="definitions-btn-primary" :disabled="saving">
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
import MethodologyClassicContent from '../../components/methodology/MethodologyClassicContent.vue';
import MethodologyClassicEditor from '../../components/methodology/MethodologyClassicEditor.vue';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const page = ref(null);

const form = reactive({
    title: '',
    introduction: '',
    sections: [],
    conclusion: '',
});

function extractPage(payload) {
    const data = payload?.data ?? payload;
    return data?.methodology_page ?? data;
}

async function loadPage() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/methodology-pages/definitions-objectifs');
        page.value = extractPage(data);
    } catch {
        error.value = 'Impossible de charger les définitions & objectifs.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.title = page.value.title;
    form.introduction = page.value.introduction ?? '';
    form.sections = JSON.parse(JSON.stringify(page.value.sections ?? []));
    form.conclusion = page.value.conclusion ?? '';
    editing.value = true;
    error.value = '';
}

function cancelEdit() {
    editing.value = false;
    error.value = '';
}

function updateClassicContent(payload) {
    form.introduction = payload.introduction;
    form.sections = payload.sections;
    form.conclusion = payload.conclusion;
}

async function save() {
    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.put('/methodology-pages/definitions-objectifs', {
            title: form.title,
            introduction: form.introduction,
            sections: form.sections,
            conclusion: form.conclusion,
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
.definitions-page {
    max-width: 52rem;
    margin: 0 auto;
}

.definitions-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.definitions-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.definitions-back:hover {
    color: #0f172a;
}

.definitions-edit-btn,
.definitions-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.definitions-edit-btn:hover,
.definitions-btn-primary:hover {
    opacity: 0.92;
}

.definitions-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.definitions-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.definitions-title {
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

.definitions-content,
.definitions-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.definitions-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.definitions-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.definitions-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.definitions-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.definitions-btn-secondary {
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
