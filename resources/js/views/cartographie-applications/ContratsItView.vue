<template>
    <div class="itc-page">
        <header class="itc-header">
            <div>
                <p class="itc-kicker">Analyse IT Audit Tool · Contracts</p>
                <h2 class="itc-title">Contrats IT</h2>
                <p class="itc-subtitle">
                    Registre des contrats prestataires — licences, souscriptions, implémentation, maintenance / support.
                </p>
            </div>
            <div class="itc-actions">
                <button type="button" class="itc-btn-secondary" @click="loadRows">Actualiser</button>
                <button type="button" class="itc-btn-primary" @click="openCreate">+ Nouveau contrat</button>
            </div>
        </header>

        <div class="itc-toolbar">
            <input
                v-model="search"
                type="search"
                class="itc-input"
                placeholder="Rechercher (fichier, prestataire, bénéficiaire, scope…)"
                @input="onSearchInput"
            />
        </div>

        <p v-if="error" class="itc-error">{{ error }}</p>
        <div v-if="loading" class="itc-empty">Chargement…</div>

        <div v-else class="itc-table-wrap">
            <table class="itc-table">
                <thead>
                    <tr>
                        <th colspan="19" class="itc-banner">Contrats IT</th>
                    </tr>
                    <tr>
                        <th>#</th>
                        <th>Nom du fichier</th>
                        <th>Lien</th>
                        <th>Prestataire</th>
                        <th>Bénéficiaire</th>
                        <th>Scope</th>
                        <th>L / S / I / M</th>
                        <th>Volume</th>
                        <th>Coût</th>
                        <th>Mécanisme de coût</th>
                        <th>Importance</th>
                        <th>Date de début</th>
                        <th>Durée</th>
                        <th>SLA</th>
                        <th>Notif. résiliation</th>
                        <th>Signé 2 parties</th>
                        <th>Remise</th>
                        <th>Responsable Achat</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in rows" :key="row.id">
                        <td>{{ row.sort_order || index + 1 }}</td>
                        <td class="itc-strong">{{ row.file_name || '—' }}</td>
                        <td>{{ row.file_link || '—' }}</td>
                        <td>{{ row.provider || '—' }}</td>
                        <td>{{ row.beneficiary || '—' }}</td>
                        <td class="itc-wide">{{ row.scope || '—' }}</td>
                        <td>{{ row.contract_type || '—' }}</td>
                        <td>{{ row.volume || '—' }}</td>
                        <td>{{ row.cost || '—' }}</td>
                        <td>{{ row.cost_mechanism || '—' }}</td>
                        <td>{{ row.importance || '—' }}</td>
                        <td>{{ row.start_date || '—' }}</td>
                        <td>{{ row.duration || '—' }}</td>
                        <td>{{ row.sla || '—' }}</td>
                        <td>{{ row.termination_notice || '—' }}</td>
                        <td>{{ row.signed_both || '—' }}</td>
                        <td>{{ row.discount || '—' }}</td>
                        <td>{{ row.purchase_owner || '—' }}</td>
                        <td class="itc-row-actions">
                            <button type="button" class="itc-link" @click="openEdit(row)">Modifier</button>
                            <button type="button" class="itc-link danger" @click="removeRow(row)">Supprimer</button>
                        </td>
                    </tr>
                    <tr v-if="!rows.length">
                        <td colspan="19" class="itc-empty-cell">Aucun contrat enregistré.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="itc-modal-backdrop" @click.self="closeForm">
            <form class="itc-modal" @submit.prevent="saveRow">
                <h3>{{ editing ? 'Modifier le contrat' : 'Nouveau contrat' }}</h3>
                <div class="itc-form-grid">
                    <label><span>#</span><input v-model.number="form.sort_order" type="number" min="0" class="itc-input" /></label>
                    <label><span>Nom du fichier</span><input v-model="form.file_name" class="itc-input" /></label>
                    <label><span>Lien du fichier</span><input v-model="form.file_link" class="itc-input" /></label>
                    <label><span>Prestataire</span><input v-model="form.provider" class="itc-input" /></label>
                    <label><span>Bénéficiaire</span><input v-model="form.beneficiary" class="itc-input" /></label>
                    <label class="itc-span-2"><span>Scope</span><textarea v-model="form.scope" rows="2" class="itc-input" /></label>
                    <label>
                        <span>Type (L/S/I/M)</span>
                        <select v-model="form.contract_type" class="itc-input">
                            <option value="">—</option>
                            <option value="L">L — Licences</option>
                            <option value="S">S — Souscription</option>
                            <option value="I">I — Implémentation</option>
                            <option value="M">M — Maintenance/Support</option>
                        </select>
                    </label>
                    <label><span>Volume</span><input v-model="form.volume" class="itc-input" /></label>
                    <label><span>Coût</span><input v-model="form.cost" class="itc-input" /></label>
                    <label><span>Mécanisme de coût</span><input v-model="form.cost_mechanism" class="itc-input" /></label>
                    <label>
                        <span>Importance</span>
                        <select v-model="form.importance" class="itc-input">
                            <option value="">—</option>
                            <option value="haute">haute</option>
                            <option value="moyenne">moyenne</option>
                            <option value="faible">faible</option>
                        </select>
                    </label>
                    <label><span>Date de début</span><input v-model="form.start_date" class="itc-input" placeholder="ex. 2024" /></label>
                    <label><span>Durée</span><input v-model="form.duration" class="itc-input" /></label>
                    <label><span>SLA</span><input v-model="form.sla" class="itc-input" /></label>
                    <label><span>Notification résiliation</span><input v-model="form.termination_notice" class="itc-input" /></label>
                    <label>
                        <span>Signé 2 parties</span>
                        <select v-model="form.signed_both" class="itc-input">
                            <option value="">—</option>
                            <option value="OUI">OUI</option>
                            <option value="NON">NON</option>
                            <option value="N/A">N/A</option>
                        </select>
                    </label>
                    <label><span>Remise</span><input v-model="form.discount" class="itc-input" /></label>
                    <label><span>Responsable Achat</span><input v-model="form.purchase_owner" class="itc-input" /></label>
                    <label class="itc-span-2"><span>Commentaires</span><textarea v-model="form.comments" rows="2" class="itc-input" /></label>
                </div>
                <p v-if="formError" class="itc-error">{{ formError }}</p>
                <div class="itc-modal-actions">
                    <button type="button" class="itc-btn-secondary" @click="closeForm">Annuler</button>
                    <button type="submit" class="itc-btn-primary" :disabled="saving">
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
        sort_order: null,
        file_name: '',
        file_link: '',
        provider: '',
        beneficiary: '',
        scope: '',
        contract_type: '',
        volume: '',
        cost: '',
        cost_mechanism: '',
        importance: '',
        start_date: '',
        duration: '',
        sla: '',
        termination_notice: '',
        signed_both: '',
        discount: '',
        purchase_owner: '',
        comments: '',
    };
}

function extractList(payload) {
    const root = payload?.data ?? payload;
    if (Array.isArray(root)) return root;
    if (Array.isArray(root?.data)) return root.data;
    return [];
}

async function loadRows() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/it-contracts', {
            params: {
                search: search.value.trim() || undefined,
                order_by_asc: 'sort_order',
                paginate: 'false',
            },
        });
        rows.value = extractList(data);
    } catch (err) {
        error.value = err.response?.data?.errors?.auth?.[0]
            || 'Impossible de charger les contrats IT.';
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
    Object.assign(form, emptyForm(), { sort_order: rows.value.length + 1 });
    formError.value = '';
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    Object.assign(form, emptyForm(), {
        sort_order: row.sort_order ?? null,
        file_name: row.file_name ?? '',
        file_link: row.file_link ?? '',
        provider: row.provider ?? '',
        beneficiary: row.beneficiary ?? '',
        scope: row.scope ?? '',
        contract_type: row.contract_type ?? '',
        volume: row.volume ?? '',
        cost: row.cost ?? '',
        cost_mechanism: row.cost_mechanism ?? '',
        importance: row.importance ?? '',
        start_date: row.start_date ?? '',
        duration: row.duration ?? '',
        sla: row.sla ?? '',
        termination_notice: row.termination_notice ?? '',
        signed_both: row.signed_both ?? '',
        discount: row.discount ?? '',
        purchase_owner: row.purchase_owner ?? '',
        comments: row.comments ?? '',
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
            await api.put(`/it-contracts/${editing.value.id}`, form);
        } else {
            await api.post('/it-contracts', form);
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
    if (!confirm(`Supprimer le contrat « ${row.file_name || row.id} » ?`)) return;
    try {
        await api.delete(`/it-contracts/${row.id}`);
        await loadRows();
    } catch {
        error.value = 'Suppression impossible.';
    }
}

onMounted(loadRows);
onUnmounted(() => clearTimeout(searchTimer));
</script>

<style scoped>
.itc-page { display: flex; flex-direction: column; gap: 1rem; padding: 1rem 1.25rem 1.5rem; }
.itc-header { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: flex-end; }
.itc-kicker { margin: 0; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #0f4c81; }
.itc-title { margin: 0.2rem 0 0; font-size: 1.35rem; font-weight: 700; color: #0f172a; }
.itc-subtitle { margin: 0.3rem 0 0; font-size: 0.875rem; color: #64748b; max-width: 42rem; }
.itc-actions, .itc-toolbar { display: flex; flex-wrap: wrap; gap: 0.65rem; }
.itc-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 0.55rem; padding: 0.55rem 0.75rem; font-size: 0.875rem; background: #fff; }
.itc-toolbar .itc-input { max-width: 28rem; }
.itc-btn-primary, .itc-btn-secondary { border-radius: 0.55rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; padding: 0.55rem 0.85rem; }
.itc-btn-primary { border: 0; background: #0f4c81; color: #fff; }
.itc-btn-secondary { border: 1px solid #cbd5e1; background: #fff; color: #334155; }
.itc-table-wrap { overflow: auto; border: 1px solid #e2e8f0; border-radius: 0.65rem; background: #fff; }
.itc-table { width: max-content; min-width: 100%; border-collapse: collapse; font-size: 0.78rem; }
.itc-table th, .itc-table td { padding: 0.45rem 0.65rem; border: 1px solid #e2e8f0; text-align: left; vertical-align: middle; white-space: nowrap; }
.itc-banner { background: #7f1d1d; color: #fff; text-align: center; font-weight: 800; font-size: 0.95rem; }
.itc-table thead tr:last-child th { background: #f8fafc; font-size: 0.68rem; font-weight: 700; color: #475569; white-space: normal; min-width: 5.5rem; max-width: 10rem; }
.itc-strong { font-weight: 700; color: #0f172a; }
.itc-wide { white-space: normal; min-width: 10rem; max-width: 16rem; }
.itc-row-actions { white-space: nowrap; }
.itc-link { border: 0; background: transparent; color: #0f4c81; font-weight: 600; cursor: pointer; padding: 0.15rem 0.35rem; }
.itc-link.danger { color: #b91c1c; }
.itc-error { margin: 0; color: #b91c1c; font-size: 0.85rem; font-weight: 600; }
.itc-empty, .itc-empty-cell { padding: 1.5rem; text-align: center; color: #64748b; }
.itc-modal-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(15,23,42,.45); }
.itc-modal { width: min(48rem, 100%); max-height: 92vh; overflow: auto; border-radius: 0.65rem; background: #fff; padding: 1.15rem 1.25rem; }
.itc-modal h3 { margin: 0 0 1rem; }
.itc-form-grid { display: grid; gap: 0.75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.itc-form-grid label { display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.78rem; font-weight: 600; color: #475569; }
.itc-span-2 { grid-column: span 2; }
.itc-modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
@media (max-width: 640px) { .itc-form-grid { grid-template-columns: 1fr; } .itc-span-2 { grid-column: span 1; } }
</style>
