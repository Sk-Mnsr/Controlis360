<template>
    <div class="cat">
        <header class="cat-head">
            <div>
                <h3>Catégories & sous-catégories</h3>
                <p>Ce paramétrage alimente le mega-menu et les filtres du front Client.</p>
            </div>
        </header>

        <div class="cat-layout">
            <form class="cat-form" @submit.prevent="saveCategory">
                <h4>{{ editingId ? 'Modifier' : 'Nouvelle catégorie' }}</h4>
                <label>
                    Libellé
                    <input v-model="form.label" required class="cat-input" />
                </label>
                <label>
                    Icône
                    <input v-model="form.icon" class="cat-input" placeholder="🚗" />
                </label>
                <label>
                    Ordre
                    <input v-model.number="form.sort_order" type="number" class="cat-input" />
                </label>
                <label>
                    Sous-catégories (une par ligne)
                    <textarea v-model="form.subcategoriesText" rows="8" class="cat-input" />
                </label>
                <label class="cat-check">
                    <input v-model="form.active" type="checkbox" />
                    Active sur le front
                </label>
                <div class="cat-form-actions">
                    <button type="submit" class="cat-btn cat-btn--primary">
                        {{ editingId ? 'Enregistrer' : 'Ajouter' }}
                    </button>
                    <button v-if="editingId" type="button" class="cat-btn" @click="resetForm">Annuler</button>
                </div>
                <p v-if="message" class="cat-msg">{{ message }}</p>
            </form>

            <div class="cat-list">
                <article v-for="cat in categories" :key="cat.id" class="cat-card">
                    <div class="cat-card-top">
                        <div>
                            <strong>{{ cat.icon }} {{ cat.label }}</strong>
                            <p>{{ cat.subcategories?.length || 0 }} sous-catégorie(s) · {{ cat.active === false ? 'Inactive' : 'Active' }}</p>
                        </div>
                        <div class="cat-card-actions">
                            <button type="button" class="cat-mini" @click="edit(cat)">Modifier</button>
                            <button type="button" class="cat-mini cat-mini--danger" @click="remove(cat)">Suppr.</button>
                        </div>
                    </div>
                    <ul>
                        <li v-for="sub in cat.subcategories" :key="sub">{{ sub }}</li>
                    </ul>
                </article>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useEncheresAdminStore } from '../../../stores/encheresAdmin';

const store = useEncheresAdminStore();
const categories = computed(() => store.categories);
const editingId = ref(null);
const message = ref('');

const form = reactive({
    label: '',
    icon: '📦',
    sort_order: 0,
    active: true,
    subcategoriesText: '',
});

function resetForm() {
    editingId.value = null;
    form.label = '';
    form.icon = '📦';
    form.sort_order = categories.value.length;
    form.active = true;
    form.subcategoriesText = '';
    message.value = '';
}

function edit(cat) {
    editingId.value = cat.id;
    form.label = cat.label;
    form.icon = cat.icon || '📦';
    form.sort_order = cat.sort_order ?? 0;
    form.active = cat.active !== false;
    form.subcategoriesText = (cat.subcategories || []).join('\n');
    message.value = '';
}

function saveCategory() {
    const subcategories = form.subcategoriesText
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);

    store.upsertCategory({
        id: editingId.value || undefined,
        label: form.label,
        icon: form.icon,
        sort_order: form.sort_order,
        active: form.active,
        subcategories,
    });

    message.value = editingId.value ? 'Catégorie mise à jour.' : 'Catégorie ajoutée.';
    resetForm();
}

function remove(cat) {
    if (!window.confirm(`Supprimer la catégorie « ${cat.label} » ?`)) return;
    store.deleteCategory(cat.id);
    if (editingId.value === cat.id) {
        resetForm();
    }
}
</script>

<style scoped>
.cat-head h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
}

.cat-head p {
    margin: 0.25rem 0 1rem;
    color: #64748b;
    font-size: 0.875rem;
}

.cat-layout {
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(260px, 320px) 1fr;
}

.cat-form,
.cat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
}

.cat-form h4 {
    margin: 0 0 0.85rem;
    font-size: 0.95rem;
    font-weight: 800;
}

.cat-form label {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    margin-bottom: 0.7rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
}

.cat-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.875rem;
}

.cat-check {
    flex-direction: row !important;
    align-items: center;
    gap: 0.5rem !important;
}

.cat-form-actions {
    display: flex;
    gap: 0.5rem;
}

.cat-btn,
.cat-mini {
    border-radius: 0.4rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 700;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
}

.cat-btn--primary {
    border: 0;
    background: #2563eb;
    color: #fff;
}

.cat-mini {
    font-size: 0.75rem;
    padding: 0.3rem 0.5rem;
}

.cat-mini--danger {
    color: #b91c1c;
    border-color: #fecaca;
}

.cat-msg {
    margin: 0.75rem 0 0;
    color: #047857;
    font-size: 0.8125rem;
    font-weight: 600;
}

.cat-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.cat-card-top {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.cat-card-top p {
    margin: 0.2rem 0 0;
    color: #64748b;
    font-size: 0.75rem;
}

.cat-card-actions {
    display: flex;
    gap: 0.35rem;
}

.cat-card ul {
    margin: 0;
    padding-left: 1.1rem;
    color: #334155;
    font-size: 0.8125rem;
}

@media (max-width: 900px) {
    .cat-layout {
        grid-template-columns: 1fr;
    }
}
</style>
