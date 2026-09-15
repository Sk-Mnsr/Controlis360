<template>
    <div class="itp-page">
        <header class="itp-header">
            <div>
                <p class="itp-kicker">Analyse IT Audit Tool · Projets</p>
                <h2 class="itp-title">Projets IT</h2>
                <p class="itp-subtitle">
                    Suivi des projets informatiques — priorité, objectif, état, avancement et owner.
                </p>
            </div>
            <div class="itp-actions">
                <button type="button" class="itp-btn-secondary" @click="loadRows">Actualiser</button>
                <button type="button" class="itp-btn-primary" @click="openCreate">+ Nouveau projet</button>
            </div>
        </header>

        <div class="itp-toolbar">
            <input
                v-model="search"
                type="search"
                class="itp-input"
                placeholder="Rechercher (projet, objectif, owner, statut…)"
                @input="onSearchInput"
            />
        </div>

        <p v-if="error" class="itp-error">{{ error }}</p>
        <div v-if="loading" class="itp-empty">Chargement…</div>

        <div v-else class="itp-table-wrap">
            <table class="itp-table">
                <thead>
                    <tr>
                        <th colspan="10" class="itp-banner">Projets IT</th>
                    </tr>
                    <tr>
                        <th>Priorité</th>
                        <th>Projet</th>
                        <th>Objectif</th>
                        <th>État actuel</th>
                        <th>Progrès (%)</th>
                        <th>Résultats et avantages</th>
                        <th>Owner</th>
                        <th>Commentaire</th>
                        <th>Livraison</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id">
                        <td class="itp-prio">{{ row.priority ?? '—' }}</td>
                        <td class="itp-strong">{{ row.name }}</td>
                        <td class="itp-wide">{{ row.objective || '—' }}</td>
                        <td>
                            <span class="itp-status" :class="statusClass(row.status)">{{ row.status || '—' }}</span>
                        </td>
                        <td>
                            <div class="itp-progress">
                                <span class="itp-progress-track">
                                    <span class="itp-progress-fill" :style="{ width: `${row.progress_pct || 0}%` }" />
                                </span>
                                <span>{{ row.progress_pct ?? 0 }}%</span>
                            </div>
                        </td>
                        <td class="itp-wide">{{ row.results_benefits || '—' }}</td>
                        <td>{{ row.owner || '—' }}</td>
                        <td class="itp-wide">{{ row.comment || '—' }}</td>
                        <td>{{ row.delivery_date || '—' }}</td>
                        <td class="itp-row-actions">
                            <button type="button" class="itp-link" @click="openEdit(row)">Modifier</button>
                            <button type="button" class="itp-link danger" @click="removeRow(row)">Supprimer</button>
                        </td>
                    </tr>
                    <tr v-if="!rows.length">
                        <td colspan="10" class="itp-empty-cell">Aucun projet enregistré.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="itp-modal-backdrop" @click.self="closeForm">
            <form class="itp-modal" @submit.prevent="saveRow">
                <h3>{{ editing ? 'Modifier le projet' : 'Nouveau projet' }}</h3>
                <div class="itp-form-grid">
                    <label><span>Priorité</span><input v-model.number="form.priority" type="number" min="0" class="itp-input" /></label>
                    <label><span>Projet *</span><input v-model="form.name" required class="itp-input" /></label>
                    <label class="itp-span-2"><span>Objectif</span><textarea v-model="form.objective" rows="2" class="itp-input" /></label>
                    <label>
                        <span>État actuel</span>
                        <select v-model="form.status" class="itp-input">
                            <option value="">—</option>
                            <option value="Not Started">Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="DONE">DONE</option>
                            <option value="Bloqué">Bloqué</option>
                        </select>
                    </label>
                    <label><span>Progrès (%)</span><input v-model.number="form.progress_pct" type="number" min="0" max="100" class="itp-input" /></label>
                    <label class="itp-span-2"><span>Résultats et avantages</span><textarea v-model="form.results_benefits" rows="2" class="itp-input" /></label>
                    <label><span>Owner</span><input v-model="form.owner" class="itp-input" /></label>
                    <label><span>Date de livraison</span><input v-model="form.delivery_date" class="itp-input" /></label>
                    <label><span>Start date</span><input v-model="form.start_date" class="itp-input" /></label>
                    <label><span>End date</span><input v-model="form.end_date" class="itp-input" /></label>
                    <label class="itp-span-2"><span>Commentaire</span><textarea v-model="form.comment" rows="2" class="itp-input" /></label>
                </div>
                <p v-if="formError" class="itp-error">{{ formError }}</p>
                <div class="itp-modal-actions">
                    <button type="button" class="itp-btn-secondary" @click="closeForm">Annuler</button>
                    <button type="submit" class="itp-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
import api from '../../api/client';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const rows = ref([]);
const search = ref('');
const showForm = ref(false);
const editing = ref(null);
const form = reactive(emptyForm());
let searchTimer = null;

function emptyForm() {
    return {
        priority: null,
        name: '',
        objective: '',
        status: '',
        progress_pct: 0,
        results_benefits: '',
        owner: '',
        comment: '',
        start_date: '',
        end_date: '',
        delivery_date: '',
    };
}

function extractList(payload) {
    const root = payload?.data ?? payload;
    if (Array.isArray(root)) return root;
    if (Array.isArray(root?.data)) return root.data;
    return [];
}

function statusClass(status) {
    const v = String(status || '').toLowerCase();
    if (v.includes('done') || v.includes('terminé')) return 'is-done';
    if (v.includes('progress') || v.includes('cours')) return 'is-progress';
    if (v.includes('block') || v.includes('bloq')) return 'is-blocked';
    return '';
}

async function loadRows() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/it-projects', {
            params: {
                search: search.value.trim() || undefined,
                paginate: 'false',
            },
        });
        rows.value = extractList(data);
    } catch (err) {
        error.value = err.response?.data?.errors?.auth?.[0]
            || 'Impossible de charger les projets IT.';
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadRows, 300);
}

function openCreate() {
    editing.value = null;
    Object.assign(form, emptyForm());
    formError.value = '';
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    Object.assign(form, emptyForm(), {
        priority: row.priority ?? null,
        name: row.name ?? '',
        objective: row.objective ?? '',
        status: row.status ?? '',
        progress_pct: row.progress_pct ?? 0,
        results_benefits: row.results_benefits ?? '',
        owner: row.owner ?? '',
        comment: row.comment ?? '',
        start_date: row.start_date ?? '',
        end_date: row.end_date ?? '',
        delivery_date: row.delivery_date ?? '',
    });
    formError.value = '';
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
}

async function saveRow() {
    saving.value = true;
    formError.value = '';
    try {
        if (editing.value) {
            await api.put(`/it-projects/${editing.value.id}`, form);
        } else {
            await api.post('/it-projects', form);
        }
        closeForm();
        await loadRows();
    } catch (err) {
        formError.value = Object.values(err.response?.data?.errors || {}).flat()[0]
            || err.response?.data?.message
            || 'Erreur lors de l’enregistrement.';
    } finally {
        saving.value = false;
    }
}

async function removeRow(row) {
    if (!confirm(`Supprimer le projet « ${row.name} » ?`)) return;
    try {
        await api.delete(`/it-projects/${row.id}`);
        await loadRows();
    } catch {
        error.value = 'Suppression impossible.';
    }
}

onMounted(loadRows);
onUnmounted(() => clearTimeout(searchTimer));
</script>

<style scoped>
.itp-page { display: flex; flex-direction: column; gap: 1rem; padding: 1rem 1.25rem 1.5rem; }
.itp-header { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: flex-end; }
.itp-kicker { margin: 0; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #0f4c81; }
.itp-title { margin: 0.2rem 0 0; font-size: 1.35rem; font-weight: 700; color: #0f172a; }
.itp-subtitle { margin: 0.3rem 0 0; font-size: 0.875rem; color: #64748b; max-width: 42rem; }
.itp-actions, .itp-toolbar { display: flex; flex-wrap: wrap; gap: 0.65rem; }
.itp-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 0.55rem; padding: 0.55rem 0.75rem; font-size: 0.875rem; background: #fff; }
.itp-toolbar .itp-input { max-width: 28rem; }
.itp-btn-primary, .itp-btn-secondary { border-radius: 0.55rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; padding: 0.55rem 0.85rem; }
.itp-btn-primary { border: 0; background: #0f4c81; color: #fff; }
.itp-btn-secondary { border: 1px solid #cbd5e1; background: #fff; color: #334155; }
.itp-table-wrap { overflow: auto; border: 1px solid #e2e8f0; border-radius: 0.65rem; background: #fff; }
.itp-table { width: max-content; min-width: 100%; border-collapse: collapse; font-size: 0.78rem; }
.itp-table th, .itp-table td { padding: 0.45rem 0.65rem; border: 1px solid #e2e8f0; text-align: left; vertical-align: middle; white-space: nowrap; }
.itp-banner { background: #1e3a5f; color: #fff; text-align: center; font-weight: 800; font-size: 0.95rem; }
.itp-table thead tr:last-child th { background: #f8fafc; font-size: 0.68rem; font-weight: 700; color: #475569; white-space: normal; min-width: 5.5rem; max-width: 11rem; }
.itp-prio { font-weight: 800; color: #0f4c81; }
.itp-strong { font-weight: 700; color: #0f172a; }
.itp-wide { white-space: normal; min-width: 10rem; max-width: 16rem; }
.itp-status { display: inline-block; padding: 0.12rem 0.45rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
.itp-status.is-done { background: #dcfce7; color: #166534; }
.itp-status.is-progress { background: #dbeafe; color: #1d4ed8; }
.itp-status.is-blocked { background: #fee2e2; color: #991b1b; }
.itp-progress { display: flex; align-items: center; gap: 0.45rem; min-width: 6rem; }
.itp-progress-track { flex: 1; height: 0.4rem; border-radius: 999px; background: #e2e8f0; overflow: hidden; min-width: 2.5rem; }
.itp-progress-fill { display: block; height: 100%; background: #0f4c81; }
.itp-row-actions { white-space: nowrap; }
.itp-link { border: 0; background: transparent; color: #0f4c81; font-weight: 600; cursor: pointer; padding: 0.15rem 0.35rem; }
.itp-link.danger { color: #b91c1c; }
.itp-error { margin: 0; color: #b91c1c; font-size: 0.85rem; font-weight: 600; }
.itp-empty, .itp-empty-cell { padding: 1.5rem; text-align: center; color: #64748b; }
.itp-modal-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(15,23,42,.45); }
.itp-modal { width: min(44rem, 100%); max-height: 92vh; overflow: auto; border-radius: 0.65rem; background: #fff; padding: 1.15rem 1.25rem; }
.itp-modal h3 { margin: 0 0 1rem; }
.itp-form-grid { display: grid; gap: 0.75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.itp-form-grid label { display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.78rem; font-weight: 600; color: #475569; }
.itp-span-2 { grid-column: span 2; }
.itp-modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
@media (max-width: 640px) { .itp-form-grid { grid-template-columns: 1fr; } .itp-span-2 { grid-column: span 1; } }
</style>
