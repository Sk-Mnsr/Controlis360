<template>
    <div
        class="methodology-page"
        :class="{
            'methodology-page-preambule': isPreambuleLayout,
            'methodology-page-grid': isGridLayout,
        }"
    >
        <header v-if="isPreambuleLayout || isGridLayout" class="preambule-header">
            <h1 class="preambule-title">{{ page?.title ?? 'Chargement...' }}</h1>
            <RouterLink :to="{ name: 'cartographie.home' }" class="methodology-home" title="Retour à l'accueil">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                    <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                    <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z" />
                </svg>
            </RouterLink>
        </header>

        <header v-else class="methodology-header">
            <img :src="logoUrl" alt="COFINA" class="methodology-logo" />
            <h1 class="methodology-title">{{ page?.title ?? 'Chargement...' }}</h1>
            <RouterLink :to="{ name: 'cartographie.home' }" class="methodology-home" title="Retour à l'accueil">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                    <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                    <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z" />
                </svg>
            </RouterLink>
        </header>

        <div v-if="loading" class="methodology-loading">Chargement...</div>
        <div v-else-if="error" class="methodology-error">{{ error }}</div>

        <template v-else-if="page">
            <div v-if="!editing" class="methodology-body">
                <div
                    v-if="isPreambuleLayout && page.body_html"
                    class="preambule-content"
                    v-html="page.body_html"
                />

                <MethodologyGrid
                    v-else-if="isGridLayout && page.grid_data"
                    :columns="page.grid_data.columns"
                    :rows="page.grid_data.rows"
                />

                <template v-else-if="!isPreambuleLayout && !isGridLayout">
                    <p v-if="page.introduction" class="methodology-intro">{{ page.introduction }}</p>

                    <section v-for="(section, index) in page.sections" :key="index" class="methodology-section">
                        <h2 class="methodology-section-title">{{ section.title }}</h2>
                        <p v-if="section.content" class="methodology-paragraph">{{ section.content }}</p>
                        <p v-if="section.subtitle" class="methodology-subtitle">{{ section.subtitle }}</p>
                        <ul v-if="section.items?.length" class="methodology-list">
                            <li v-for="(item, itemIndex) in section.items" :key="itemIndex">{{ item }}</li>
                        </ul>
                    </section>

                    <p v-if="page.conclusion" class="methodology-conclusion">{{ page.conclusion }}</p>
                </template>
            </div>

            <form v-else class="methodology-edit" @submit.prevent="savePage">
                <div>
                    <label class="edit-label">Titre</label>
                    <input v-model="form.title" required class="edit-input" />
                </div>

                <template v-if="isPreambuleLayout">
                    <div>
                        <label class="edit-label">Contenu HTML</label>
                        <textarea v-model="form.body_html" rows="24" class="edit-textarea font-mono text-xs" />
                    </div>
                </template>

                <MethodologyGridEditor
                    v-else-if="isGridLayout"
                    :columns="form.grid_data.columns"
                    :rows="form.grid_data.rows"
                    @update="form.grid_data = $event"
                />

                <template v-else>
                    <div>
                        <label class="edit-label">Introduction</label>
                        <textarea v-model="form.introduction" rows="4" class="edit-textarea" />
                    </div>

                    <div v-for="(section, index) in form.sections" :key="index" class="edit-section">
                        <label class="edit-label">Section {{ index + 1 }} — Titre</label>
                        <input v-model="section.title" required class="edit-input" />
                        <label class="edit-label">Contenu</label>
                        <textarea v-model="section.content" rows="3" class="edit-textarea" />
                        <label class="edit-label">Sous-titre (optionnel)</label>
                        <input v-model="section.subtitle" class="edit-input" />
                        <label class="edit-label">Éléments de liste (un par ligne)</label>
                        <textarea
                            :value="sectionItemsText[index]"
                            rows="4"
                            class="edit-textarea"
                            @input="updateSectionItems(index, $event.target.value)"
                        />
                    </div>

                    <div>
                        <label class="edit-label">Conclusion</label>
                        <textarea v-model="form.conclusion" rows="4" class="edit-textarea" />
                    </div>
                </template>

                <div class="edit-actions">
                    <button type="button" class="edit-btn-secondary" @click="cancelEdit">Annuler</button>
                    <button type="submit" class="edit-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>

            <div v-if="canEditMethodology && !editing" class="methodology-actions">
                <button type="button" class="edit-btn-primary" @click="startEdit">Modifier le contenu</button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import { useCartographiePermissions } from '../../composables/useCartographiePermissions';

const route = useRoute();
const { canEditMethodology } = useCartographiePermissions();

const logoUrl = '/logo_Cofina.png';

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const page = ref(null);

const isPreambuleLayout = computed(() => page.value?.layout === 'preambule');
const isGridLayout = computed(() => page.value?.layout === 'grid');

const form = reactive({
    title: '',
    introduction: '',
    sections: [],
    conclusion: '',
    body_html: '',
    grid_data: { columns: [], rows: [] },
});

const sectionItemsText = ref([]);

function extractPage(payload) {
    const data = payload?.data ?? payload;
    return data?.methodology_page ?? data?.MethodologyPage ?? data;
}

async function loadPage() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/methodology-pages/${route.params.slug}`);
        page.value = extractPage(data);
    } catch (err) {
        error.value = err.response?.data?.errors?.slug?.[0] ?? 'Impossible de charger la page.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.title = page.value.title;
    form.introduction = page.value.introduction ?? '';
    form.sections = JSON.parse(JSON.stringify(page.value.sections ?? []));
    form.conclusion = page.value.conclusion ?? '';
    form.body_html = page.value.body_html ?? '';
    form.grid_data = JSON.parse(JSON.stringify(page.value.grid_data ?? { columns: [], rows: [] }));
    sectionItemsText.value = form.sections.map((s) => (s.items ?? []).join('\n'));
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
}

function updateSectionItems(index, value) {
    sectionItemsText.value[index] = value;
    form.sections[index].items = value
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
}

async function savePage() {
    saving.value = true;

    try {
        const payload = { title: form.title };

        if (isPreambuleLayout.value) {
            payload.body_html = form.body_html;
        } else if (isGridLayout.value) {
            payload.grid_data = form.grid_data;
        } else {
            payload.introduction = form.introduction;
            payload.sections = form.sections;
            payload.conclusion = form.conclusion;
        }

        const { data } = await api.put(`/methodology-pages/${route.params.slug}`, payload);
        page.value = extractPage(data);
        editing.value = false;
    } catch {
        error.value = 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}

onMounted(loadPage);
</script>

<style scoped>
.methodology-page {
    margin: -1.5rem -1.5rem 0;
    min-height: 100%;
    background: #ffffff;
    padding: 2rem 2.5rem 3rem;
    color: #111111;
    font-family: Arial, Helvetica, sans-serif;
}

@media (min-width: 1024px) {
    .methodology-page {
        margin: -2rem -2rem 0;
        padding: 2.5rem 3.5rem 4rem;
    }
}

.preambule-header {
    position: relative;
    margin-bottom: 2rem;
    text-align: center;
}

.preambule-header .methodology-home {
    position: absolute;
    top: 0;
    right: 0;
}

.preambule-title {
    display: inline-block;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: underline;
    color: #111111;
}

.methodology-header {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.methodology-logo {
    height: 2.75rem;
    width: auto;
    object-fit: contain;
}

.methodology-title {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #c00000;
}

.methodology-home {
    color: #111111;
    transition: color 0.15s;
}

.methodology-home:hover {
    color: #c00000;
}

.methodology-loading,
.methodology-error {
    font-size: 0.9rem;
    color: #64748b;
}

.methodology-error {
    color: #b91c1c;
}

.methodology-body {
    max-width: 52rem;
    font-size: 0.95rem;
    line-height: 1.55;
}

.methodology-page-preambule .methodology-body,
.methodology-page-grid .methodology-body {
    max-width: 56rem;
    margin: 0 auto;
}

.methodology-page-grid .methodology-body {
    max-width: none;
    width: 100%;
}

.methodology-intro,
.methodology-paragraph,
.methodology-conclusion {
    margin-bottom: 1.25rem;
    text-align: justify;
}

.methodology-section {
    margin-bottom: 1.5rem;
}

.methodology-section-title {
    margin-bottom: 0.75rem;
    font-size: 1rem;
    font-weight: 700;
    font-style: italic;
}

.methodology-subtitle {
    margin-bottom: 0.5rem;
    font-weight: 700;
    font-style: italic;
}

.methodology-list {
    margin: 0.5rem 0 0;
    padding: 0;
    list-style: none;
}

.methodology-list li {
    margin-bottom: 0.35rem;
    padding-left: 0.25rem;
}

.methodology-list li::before {
    content: '- ';
}

.methodology-actions {
    margin-top: 2rem;
}

.methodology-edit {
    max-width: 52rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.edit-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
}

.edit-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.edit-input,
.edit-textarea {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-family: inherit;
}

.edit-textarea {
    resize: vertical;
}

.edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.edit-btn-primary,
.edit-btn-secondary {
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.edit-btn-primary {
    background: #047857;
    color: #ffffff;
}

.edit-btn-primary:hover {
    background: #065f46;
}

.edit-btn-primary:disabled {
    opacity: 0.6;
}

.edit-btn-secondary {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
}
</style>

<style>
.preambule-content p {
    margin-bottom: 1rem;
    text-align: justify;
}

.preambule-content .mp-red {
    color: #c00000;
    font-weight: 700;
}

.preambule-content .mp-blue,
.preambule-content a.mp-blue {
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.preambule-content a.mp-blue:hover {
    text-decoration: underline;
}

.preambule-content .mp-green {
    color: #15803d;
}

.preambule-content .mp-bold {
    font-weight: 700;
}

.preambule-content .mp-section-title {
    margin: 1.5rem 0 1rem;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: underline;
    color: #c00000;
}

.preambule-content .mp-step-title {
    margin: 1.25rem 0 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    text-decoration: underline;
}

.preambule-content .mp-footer {
    margin-top: 2rem;
    font-size: 1.15rem;
    font-weight: 700;
    text-align: center;
    color: #c00000;
}
</style>
