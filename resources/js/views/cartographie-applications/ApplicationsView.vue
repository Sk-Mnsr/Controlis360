<template>
    <div class="apps-page">
        <header class="apps-page-header">
            <div>
                <p class="apps-page-kicker">Inventaire</p>
                <h2 class="apps-page-title">Applications</h2>
                <p class="apps-page-subtitle">
                    Référentiel inventaire — structure CARTOGRAPHIE_APP COFSN
                </p>
            </div>
            <button type="button" class="apps-btn-primary" @click="openCreate">
                + Nouvelle application
            </button>
        </header>

        <div class="apps-toolbar">
            <input
                v-model="search"
                type="search"
                class="apps-input"
                placeholder="Rechercher (ID, nom, domaine, technologie…)"
                @input="onSearchInput"
            />
            <button type="button" class="apps-btn-secondary" @click="loadApplications">
                Actualiser
            </button>
        </div>

        <p v-if="error" class="apps-error">{{ error }}</p>
        <div v-if="loading" class="apps-empty">Chargement…</div>

        <div v-else class="apps-table-wrap">
            <table class="apps-table">
                <thead>
                    <tr class="apps-group-row">
                        <th colspan="11" class="apps-group-main">Informations générales</th>
                        <th colspan="3" class="apps-group-license">Licences</th>
                        <th colspan="2" class="apps-group-renewal">Renouvellement licence</th>
                        <th colspan="5" class="apps-group-main">Infrastructure</th>
                        <th colspan="4" class="apps-group-sol">Solution</th>
                        <th rowspan="2" class="apps-th-actions">Actions</th>
                    </tr>
                    <tr class="apps-col-row">
                        <th>ID</th>
                        <th>Nom de l'application</th>
                        <th>Domaine métier</th>
                        <th>Fonction principale</th>
                        <th>Utilisateurs</th>
                        <th>Technologie</th>
                        <th>Type</th>
                        <th>Criticité</th>
                        <th>Statut</th>
                        <th>Responsable / Équipe</th>
                        <th>Date mise en service</th>
                        <th class="apps-th-license">Version</th>
                        <th class="apps-th-license">Date d'expiration</th>
                        <th class="apps-th-license">MAJ</th>
                        <th class="apps-th-renewal">Dernière date</th>
                        <th class="apps-th-renewal">Prochaine date</th>
                        <th>Serveur / Infrastructure</th>
                        <th>Type d'hébergement</th>
                        <th>Sauvegardes</th>
                        <th>SLA existant</th>
                        <th>Commentaire</th>
                        <th>Editeur</th>
                        <th>Importance</th>
                        <th>Version</th>
                        <th>Dernière version</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="app in applications" :key="app.id">
                        <td class="apps-code">{{ app.code || '—' }}</td>
                        <td class="apps-name">
                            {{ app.name || '—' }}
                            <span v-if="app.application_type" class="apps-type-link">
                                {{ app.application_type.code }}
                            </span>
                        </td>
                        <td>{{ app.business_domain || '—' }}</td>
                        <td class="apps-cell-wide">{{ app.main_function || '—' }}</td>
                        <td>{{ app.users || '—' }}</td>
                        <td>{{ app.technology || '—' }}</td>
                        <td>{{ app.type || '—' }}</td>
                        <td>
                            <span
                                v-if="app.criticality"
                                class="apps-badge"
                                :class="`crit-${app.criticality}`"
                            >
                                {{ app.criticality_fr || app.criticality }}
                            </span>
                            <span v-else>—</span>
                        </td>
                        <td>{{ app.status_fr || app.status || '—' }}</td>
                        <td>{{ app.owner || '—' }}</td>
                        <td>{{ formatDate(app.go_live_date) }}</td>
                        <td class="apps-td-license">{{ app.license_version || '—' }}</td>
                        <td class="apps-td-license">{{ formatDate(app.license_expiry_date) }}</td>
                        <td class="apps-td-license">{{ app.license_update || '—' }}</td>
                        <td class="apps-td-renewal">{{ formatDate(app.license_last_renewal_date) }}</td>
                        <td class="apps-td-renewal">{{ formatDate(app.license_next_renewal_date) }}</td>
                        <td>{{ app.infrastructure || '—' }}</td>
                        <td>{{ app.hosting_type || '—' }}</td>
                        <td>{{ app.backup || '—' }}</td>
                        <td>{{ app.sla || '—' }}</td>
                        <td class="apps-cell-wide">{{ app.comment || '—' }}</td>
                        <td>{{ app.editor || '—' }}</td>
                        <td>{{ app.importance || '—' }}</td>
                        <td>{{ app.version || '—' }}</td>
                        <td>{{ app.last_version || '—' }}</td>
                        <td class="apps-actions">
                            <button type="button" class="apps-link" @click="openEdit(app)">Modifier</button>
                            <button type="button" class="apps-link danger" @click="removeApplication(app)">
                                Supprimer
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!applications.length">
                        <td colspan="26" class="apps-empty-cell">Aucune application enregistrée.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="apps-modal-backdrop" @click.self="closeForm">
            <form class="apps-modal" @submit.prevent="saveApplication">
                <h3>{{ editing ? 'Modifier l’application' : 'Nouvelle application' }}</h3>

                <section class="apps-form-section">
                    <h4>Informations générales</h4>
                    <div class="apps-form-grid">
                        <label>
                            <span>ID</span>
                            <input v-model="form.code" class="apps-input" maxlength="50" placeholder="APP-01 (auto si vide)" />
                        </label>
                        <label>
                            <span>Nom de l'application *</span>
                            <input v-model="form.name" required class="apps-input" placeholder="ex. FLEXCUBE" />
                        </label>
                        <label>
                            <span>Domaine métier</span>
                            <input v-model="form.business_domain" class="apps-input" placeholder="ex. Core Banking" />
                        </label>
                        <label>
                            <span>Fonction principale</span>
                            <input v-model="form.main_function" class="apps-input" />
                        </label>
                        <label>
                            <span>Utilisateurs</span>
                            <input v-model="form.users" class="apps-input" placeholder="ex. Agences / Back-office" />
                        </label>
                        <label>
                            <span>Technologie</span>
                            <input v-model="form.technology" class="apps-input" placeholder="ex. Oracle" />
                        </label>
                        <label>
                            <span>Type</span>
                            <input v-model="form.type" class="apps-input" placeholder="ex. Cœur métier" />
                        </label>
                        <label>
                            <span>Criticité</span>
                            <select v-model="form.criticality" class="apps-input">
                                <option value="">—</option>
                                <option value="faible">Faible</option>
                                <option value="moyenne">Moyenne</option>
                                <option value="haute">Haute</option>
                                <option value="critique">Critique</option>
                            </select>
                        </label>
                        <label>
                            <span>Statut</span>
                            <select v-model="form.status" class="apps-input">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="planned">Planifiée</option>
                                <option value="retired">Retirée</option>
                            </select>
                        </label>
                        <label>
                            <span>Responsable / Contact</span>
                            <input v-model="form.owner" class="apps-input" placeholder="ex. DSI/GROUP" />
                        </label>
                        <label>
                            <span>Date mise en service</span>
                            <input v-model="form.go_live_date" type="date" class="apps-input" />
                        </label>
                        <label>
                            <span>Type applicatif (Services IT)</span>
                            <select v-model="form.application_type_id" class="apps-input">
                                <option :value="null">— Non lié —</option>
                                <option
                                    v-for="type in applicationTypes"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.code }} — {{ type.name }}
                                </option>
                            </select>
                        </label>
                        <label>
                            <span>Environnement</span>
                            <select v-model="form.environment_id" class="apps-input">
                                <option :value="null">—</option>
                                <option v-for="env in environments" :key="env.id" :value="env.id">
                                    {{ env.name }}
                                </option>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="apps-form-section apps-form-license">
                    <h4>Licences</h4>
                    <div class="apps-form-grid apps-form-grid-3">
                        <label>
                            <span>Version</span>
                            <input v-model="form.license_version" class="apps-input" />
                        </label>
                        <label>
                            <span>Date d'expiration</span>
                            <input v-model="form.license_expiry_date" type="date" class="apps-input" />
                        </label>
                        <label>
                            <span>MAJ</span>
                            <input v-model="form.license_update" class="apps-input" />
                        </label>
                    </div>
                </section>

                <section class="apps-form-section apps-form-renewal">
                    <h4>Renouvellement licence</h4>
                    <div class="apps-form-grid">
                        <label>
                            <span>Dernière date</span>
                            <input v-model="form.license_last_renewal_date" type="date" class="apps-input" />
                        </label>
                        <label>
                            <span>Prochaine date</span>
                            <input v-model="form.license_next_renewal_date" type="date" class="apps-input" />
                        </label>
                    </div>
                </section>

                <section class="apps-form-section">
                    <h4>Infrastructure</h4>
                    <div class="apps-form-grid">
                        <label>
                            <span>Serveur / Infrastructure</span>
                            <input v-model="form.infrastructure" class="apps-input" />
                        </label>
                        <label>
                            <span>Type d'hébergement</span>
                            <input v-model="form.hosting_type" class="apps-input" />
                        </label>
                        <label>
                            <span>Sauvegardes</span>
                            <input v-model="form.backup" class="apps-input" />
                        </label>
                        <label>
                            <span>SLA existant</span>
                            <input v-model="form.sla" class="apps-input" />
                        </label>
                        <label class="apps-span-2">
                            <span>Commentaire</span>
                            <textarea v-model="form.comment" rows="2" class="apps-input" />
                        </label>
                    </div>
                </section>

                <section class="apps-form-section">
                    <h4>Solution</h4>
                    <div class="apps-form-grid">
                        <label>
                            <span>Editeur</span>
                            <input v-model="form.editor" class="apps-input" />
                        </label>
                        <label>
                            <span>Importance</span>
                            <input v-model="form.importance" class="apps-input" placeholder="ex. Primordial" />
                        </label>
                        <label>
                            <span>Version</span>
                            <input v-model="form.version" class="apps-input" />
                        </label>
                        <label>
                            <span>Dernière version</span>
                            <input v-model="form.last_version" class="apps-input" />
                        </label>
                    </div>
                </section>

                <p v-if="formError" class="apps-error">{{ formError }}</p>

                <div class="apps-modal-actions">
                    <button type="button" class="apps-btn-secondary" @click="closeForm">Annuler</button>
                    <button type="submit" class="apps-btn-primary" :disabled="saving">
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
const applications = ref([]);
const environments = ref([]);
const applicationTypes = ref([]);
const search = ref('');
const showForm = ref(false);
const editing = ref(null);

const form = reactive(emptyForm());

let searchTimer = null;

function emptyForm() {
    return {
        code: '',
        name: '',
        business_domain: '',
        main_function: '',
        users: '',
        technology: '',
        type: '',
        criticality: '',
        status: 'active',
        owner: '',
        go_live_date: '',
        license_version: '',
        license_expiry_date: '',
        license_update: '',
        license_last_renewal_date: '',
        license_next_renewal_date: '',
        infrastructure: '',
        hosting_type: '',
        backup: '',
        sla: '',
        comment: '',
        editor: '',
        importance: '',
        version: '',
        last_version: '',
        environment_id: null,
        application_type_id: null,
    };
}

function toDateInput(value) {
    if (!value) return '';
    const str = String(value);
    return str.length >= 10 ? str.slice(0, 10) : str;
}

function formatDate(value) {
    if (!value) return '—';
    const str = toDateInput(value);
    const [y, m, d] = str.split('-');
    if (!y || !m || !d) return str;
    return `${d}/${m}/${y}`;
}

function extractList(payload) {
    const root = payload?.data ?? payload;
    if (Array.isArray(root)) return root;
    if (Array.isArray(root?.data)) return root.data;
    return [];
}

async function loadEnvironments() {
    try {
        const { data } = await api.get('/environments', { params: { paginate: 'false', order_by_asc: 'name' } });
        environments.value = extractList(data);
    } catch {
        environments.value = [];
    }
}

async function loadApplicationTypes() {
    try {
        const { data } = await api.get('/it-services/dashboard');
        const root = data?.data ?? data;
        const services = root?.services ?? [];
        applicationTypes.value = (Array.isArray(services) ? services : []).map((row) => ({
            id: row.application_type_id,
            code: row.code,
            name: row.name,
        }));
    } catch {
        applicationTypes.value = [];
    }
}

async function loadApplications() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/applications', {
            params: {
                search: search.value.trim() || undefined,
                order_by_asc: 'code',
                paginate: 'false',
            },
        });
        applications.value = extractList(data);
    } catch (err) {
        const status = err.response?.status;
        const errors = err.response?.data?.errors;
        const serverMessage = err.response?.data?.message
            || errors?.message?.[0]
            || errors?.auth?.[0]
            || (typeof errors === 'string' ? errors : null);

        if (status === 403) {
            error.value = serverMessage || 'Accès non autorisé à l’inventaire des applications.';
        } else if (status === 500 || status === 404) {
            error.value = serverMessage
                ? `Impossible de charger les applications : ${serverMessage}`
                : 'Impossible de charger les applications (erreur serveur).';
        } else {
            error.value = serverMessage
                ? `Impossible de charger les applications : ${serverMessage}`
                : 'Impossible de charger les applications.';
        }

        applications.value = [];
    } finally {
        loading.value = false;
    }
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadApplications, 300);
}

function openCreate() {
    editing.value = null;
    Object.assign(form, emptyForm());
    formError.value = '';
    showForm.value = true;
}

function openEdit(app) {
    editing.value = app;
    Object.assign(form, {
        code: app.code ?? '',
        name: app.name ?? '',
        business_domain: app.business_domain ?? '',
        main_function: app.main_function ?? '',
        users: app.users ?? '',
        technology: app.technology ?? '',
        type: app.type ?? '',
        criticality: app.criticality ?? '',
        status: app.status ?? 'active',
        owner: app.owner ?? '',
        go_live_date: toDateInput(app.go_live_date),
        license_version: app.license_version ?? '',
        license_expiry_date: toDateInput(app.license_expiry_date),
        license_update: app.license_update ?? '',
        license_last_renewal_date: toDateInput(app.license_last_renewal_date),
        license_next_renewal_date: toDateInput(app.license_next_renewal_date),
        infrastructure: app.infrastructure ?? '',
        hosting_type: app.hosting_type ?? '',
        backup: app.backup ?? '',
        sla: app.sla ?? '',
        comment: app.comment ?? '',
        editor: app.editor ?? '',
        importance: app.importance ?? '',
        version: app.version ?? '',
        last_version: app.last_version ?? '',
        environment_id: app.environment_id ?? app.environment?.id ?? null,
        application_type_id: app.application_type_id ?? app.application_type?.id ?? null,
    });
    formError.value = '';
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
    formError.value = '';
}

function extractError(err) {
    const data = err.response?.data;
    if (!data) return 'Erreur lors de l’enregistrement.';
    if (typeof data.message === 'string' && data.message.trim()) return data.message;
    const errors = data.errors ?? data.data;
    if (errors) {
        const first = Object.values(errors).flat()[0];
        if (first) return first;
    }
    return 'Erreur lors de l’enregistrement.';
}

function trimOrNull(value) {
    if (value == null) return null;
    const trimmed = String(value).trim();
    return trimmed === '' ? null : trimmed;
}

async function saveApplication() {
    saving.value = true;
    formError.value = '';

    const payload = {
        code: trimOrNull(form.code) || undefined,
        name: form.name.trim(),
        business_domain: trimOrNull(form.business_domain),
        main_function: trimOrNull(form.main_function),
        users: trimOrNull(form.users),
        technology: trimOrNull(form.technology),
        type: trimOrNull(form.type),
        criticality: form.criticality || null,
        status: form.status || 'active',
        owner: trimOrNull(form.owner),
        go_live_date: form.go_live_date || null,
        license_version: trimOrNull(form.license_version),
        license_expiry_date: form.license_expiry_date || null,
        license_update: trimOrNull(form.license_update),
        license_last_renewal_date: form.license_last_renewal_date || null,
        license_next_renewal_date: form.license_next_renewal_date || null,
        infrastructure: trimOrNull(form.infrastructure),
        hosting_type: trimOrNull(form.hosting_type),
        backup: trimOrNull(form.backup),
        sla: trimOrNull(form.sla),
        comment: trimOrNull(form.comment),
        editor: trimOrNull(form.editor),
        importance: trimOrNull(form.importance),
        version: trimOrNull(form.version),
        last_version: trimOrNull(form.last_version),
        environment_id: form.environment_id || null,
        application_type_id: form.application_type_id || null,
    };

    try {
        if (editing.value) {
            await api.put(`/applications/${editing.value.id}`, payload);
        } else {
            await api.post('/applications', payload);
        }
        closeForm();
        await loadApplications();
    } catch (err) {
        formError.value = extractError(err);
    } finally {
        saving.value = false;
    }
}

async function removeApplication(app) {
    if (!confirm(`Supprimer l’application « ${app.name} » ?`)) return;

    try {
        await api.delete(`/applications/${app.id}`);
        await loadApplications();
    } catch (err) {
        alert(extractError(err));
    }
}

onMounted(async () => {
    await Promise.all([loadEnvironments(), loadApplicationTypes()]);
    await loadApplications();
});

onUnmounted(() => clearTimeout(searchTimer));
</script>

<style scoped>
.apps-page {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 1.25rem 1.5rem;
    max-width: none;
}

.apps-page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

.apps-page-kicker {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #0f4c81;
}

.apps-page-title {
    margin: 0.2rem 0 0;
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
}

.apps-page-subtitle {
    margin: 0.3rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
}

.apps-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.apps-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.55rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.875rem;
    background: #fff;
}

.apps-toolbar .apps-input {
    max-width: 28rem;
}

.apps-btn-primary,
.apps-btn-secondary,
.apps-link {
    border-radius: 0.55rem;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.apps-btn-primary {
    border: 0;
    background: #0f4c81;
    color: #fff;
    padding: 0.6rem 0.95rem;
}

.apps-btn-secondary {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    padding: 0.55rem 0.85rem;
}

.apps-link {
    border: 0;
    background: transparent;
    color: #0f4c81;
    padding: 0.15rem 0.35rem;
    white-space: nowrap;
}

.apps-link.danger {
    color: #b91c1c;
}

.apps-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    background: #fff;
}

.apps-table {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}

.apps-table th,
.apps-table td {
    padding: 0.45rem 0.65rem;
    border: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.apps-group-row th {
    text-align: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: #fff;
    padding: 0.4rem 0.5rem;
}

.apps-group-main {
    background: #c41e3a;
}

.apps-group-sol {
    background: #0f6666;
}

.apps-group-license {
    background: #e6b800;
    color: #1e293b !important;
}

.apps-group-renewal {
    background: #7dd3fc;
    color: #0f172a !important;
}

.apps-col-row th {
    background: #f8fafc;
    font-size: 0.68rem;
    font-weight: 700;
    color: #475569;
    text-transform: none;
    white-space: normal;
    min-width: 6.5rem;
    max-width: 11rem;
    line-height: 1.25;
}

.apps-th-license {
    background: #fef9c3 !important;
}

.apps-th-renewal {
    background: #e0f2fe !important;
}

.apps-th-actions {
    background: #c41e3a;
    color: #fff;
    min-width: 7rem;
}

.apps-td-license {
    background: #fffbeb;
}

.apps-td-renewal {
    background: #f0f9ff;
}

.apps-code {
    font-weight: 700;
    color: #0f4c81;
}

.apps-name {
    font-weight: 600;
    color: #0f172a;
}

.apps-type-link {
    display: inline-block;
    margin-left: 0.4rem;
    padding: 0.05rem 0.4rem;
    border-radius: 0.3rem;
    background: #e0f2fe;
    color: #0f4c81;
    font-size: 0.68rem;
    font-weight: 700;
}

.apps-cell-wide {
    white-space: normal;
    min-width: 10rem;
    max-width: 16rem;
}

.apps-badge {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
}

.crit-faible {
    background: #dcfce7;
    color: #166534;
}

.crit-moyenne {
    background: #fef9c3;
    color: #854d0e;
}

.crit-haute {
    background: #ffedd5;
    color: #c2410c;
}

.crit-critique {
    background: #fee2e2;
    color: #b91c1c;
}

.apps-actions {
    position: sticky;
    right: 0;
    background: #fff;
    box-shadow: -4px 0 8px rgba(15, 23, 42, 0.06);
}

.apps-empty,
.apps-empty-cell {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.apps-error {
    margin: 0;
    color: #b91c1c;
    font-size: 0.85rem;
}

.apps-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.45);
}

.apps-modal {
    width: min(52rem, 100%);
    max-height: 92vh;
    overflow: auto;
    border-radius: 0.9rem;
    background: #fff;
    padding: 1.25rem 1.35rem;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
}

.apps-modal h3 {
    margin: 0 0 1rem;
    font-size: 1.05rem;
    font-weight: 700;
}

.apps-form-section {
    margin-bottom: 1.1rem;
    padding: 0.85rem 0.95rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #f8fafc;
}

.apps-form-section h4 {
    margin: 0 0 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #c41e3a;
}

.apps-form-license {
    background: #fffbeb;
    border-color: #fde68a;
}

.apps-form-license h4 {
    color: #a16207;
}

.apps-form-renewal {
    background: #f0f9ff;
    border-color: #bae6fd;
}

.apps-form-renewal h4 {
    color: #0369a1;
}

.apps-form-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.apps-form-grid-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.apps-form-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
}

.apps-span-2 {
    grid-column: span 2;
}

.apps-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

@media (max-width: 800px) {
    .apps-form-grid,
    .apps-form-grid-3 {
        grid-template-columns: 1fr;
    }

    .apps-span-2 {
        grid-column: span 1;
    }
}
</style>
