<template>
    <div class="preambule-page">
        <div class="preambule-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="preambule-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="isSuperAdmin && !editing"
                type="button"
                class="preambule-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="preambule-loading">Chargement...</div>

        <template v-else>
            <header class="preambule-title">{{ page?.title ?? 'Préambule' }}</header>

            <p v-if="error && !page" class="preambule-error">{{ error }}</p>

            <div v-else-if="!editing" class="preambule-body">
                <MethodologyPreambuleContent :html="page.body_html" />
            </div>

            <form v-else class="preambule-form" @submit.prevent="save">
                <div>
                    <label class="preambule-label">Titre</label>
                    <input v-model="form.title" required class="preambule-input" />
                </div>

                <div>
                    <label class="preambule-label">Contenu HTML</label>
                    <textarea v-model="form.body_html" rows="28" class="preambule-textarea font-mono text-xs" />
                </div>

                <p v-if="error" class="preambule-error">{{ error }}</p>

                <div class="preambule-form-actions">
                    <button type="button" class="preambule-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="preambule-btn-primary" :disabled="saving">
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
import MethodologyPreambuleContent from '../../components/methodology/MethodologyPreambuleContent.vue';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const page = ref(null);

const form = reactive({
    title: '',
    body_html: '',
});

function extractPage(payload) {
    const data = payload?.data ?? payload;
    return data?.methodology_page ?? data;
}

async function loadPage() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/methodology-pages/preambule');
        page.value = extractPage(data);
    } catch {
        error.value = 'Impossible de charger le préambule.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.title = page.value.title;
    form.body_html = page.value.body_html ?? '';
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
        const { data } = await api.put('/methodology-pages/preambule', {
            title: form.title,
            body_html: form.body_html,
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
.preambule-page {
    max-width: 52rem;
    margin: 0 auto;
}

.preambule-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.preambule-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.preambule-back:hover {
    color: #0f172a;
}

.preambule-edit-btn,
.preambule-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.preambule-edit-btn:hover,
.preambule-btn-primary:hover {
    opacity: 0.92;
}

.preambule-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.preambule-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.preambule-title {
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

.preambule-body {
    font-size: 0.95rem;
    line-height: 1.55;
    color: #111111;
}

.preambule-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.preambule-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.preambule-input,
.preambule-textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.preambule-textarea {
    resize: vertical;
}

.preambule-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.preambule-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.preambule-btn-secondary {
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
