<template>
    <div class="rcg-page">
        <header class="rcg-header">
            <div class="rcg-header-main">
                <RouterLink :to="{ name: 'gouvernance-it.home' }" class="rcg-back">
                    ← Gouvernance IT
                </RouterLink>
                <h1 class="rcg-title">Registre de comptes génériques</h1>
                <p class="rcg-hint">
                    L’Agent IT enregistre / modifie les lignes. Le Responsable IT valide les créations et modifications.
                </p>
            </div>

            <div class="rcg-header-actions">
                <label v-if="canSelectFiliale && filiales.length" class="rcg-env">
                    <span>Filiale</span>
                    <select v-model="selectedEnvironmentId" @change="loadRows">
                        <option
                            v-for="filiale in filiales"
                            :key="filiale.id"
                            :value="filiale.id"
                        >
                            {{ filiale.name || filiale.code }}
                        </option>
                    </select>
                </label>

                <button
                    v-if="permissions.can_create"
                    type="button"
                    class="rcg-btn rcg-btn-primary"
                    @click="openCreate"
                >
                    + Nouvelle ligne
                </button>
            </div>
        </header>

        <p v-if="error" class="rcg-error">{{ error }}</p>
        <p v-if="success" class="rcg-success">{{ success }}</p>
        <div v-if="loading" class="rcg-loading">Chargement…</div>

        <div v-else class="rcg-table-wrap">
            <table class="rcg-table">
                <thead>
                    <tr>
                        <th colspan="24" class="rcg-banner">
                            Registre de documentation — Comptes génériques
                        </th>
                    </tr>
                    <tr>
                        <th>USER_ID</th>
                        <th>USER_NAME</th>
                        <th>Statut</th>
                        <th>Forgotten</th>
                        <th>Type de compte</th>
                        <th>Utilité / Finalité</th>
                        <th>Système / Application</th>
                        <th>Owner / Responsable</th>
                        <th>Utilisation</th>
                        <th>Privilèges / Rôle</th>
                        <th>Compte nominatif associé</th>
                        <th>Dernière revue</th>
                        <th>Action / Observation</th>
                        <th>Justification de l’existence</th>
                        <th>Mode d’utilisation</th>
                        <th>Accès interactif</th>
                        <th>Mot de passe géré par</th>
                        <th>MFA</th>
                        <th>Journalisation</th>
                        <th>Revue périodique</th>
                        <th>Risque</th>
                        <th>Mesure corrective</th>
                        <th>Validation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="24" class="rcg-empty">Aucun compte générique enregistré.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td class="rcg-strong">{{ row.user_id }}</td>
                        <td>{{ row.user_name || '—' }}</td>
                        <td>{{ row.statut || '—' }}</td>
                        <td class="rcg-center">{{ row.forgotten || '—' }}</td>
                        <td>{{ row.account_type || '—' }}</td>
                        <td>{{ row.purpose || '—' }}</td>
                        <td>{{ row.system_application || '—' }}</td>
                        <td>{{ row.owner || '—' }}</td>
                        <td>{{ row.usage || '—' }}</td>
                        <td>{{ row.privileges_role || '—' }}</td>
                        <td>{{ row.associated_nominative_account || '—' }}</td>
                        <td class="rcg-center">{{ formatDate(row.last_review_date) }}</td>
                        <td>{{ row.action_observation || '—' }}</td>
                        <td>{{ row.existence_justification || '—' }}</td>
                        <td class="rcg-center">{{ row.usage_mode || '—' }}</td>
                        <td class="rcg-center">{{ row.interactive_access || '—' }}</td>
                        <td>{{ row.password_managed_by || '—' }}</td>
                        <td class="rcg-center">{{ row.mfa || '—' }}</td>
                        <td class="rcg-center">{{ row.logging_enabled || '—' }}</td>
                        <td class="rcg-center">{{ row.periodic_review || '—' }}</td>
                        <td class="rcg-center">
                            <span class="rcg-risk" :class="riskClass(row.risk)">{{ row.risk || '—' }}</span>
                        </td>
                        <td>{{ row.corrective_measure || '—' }}</td>
                        <td class="rcg-center">
                            <span
                                class="rcg-status"
                                :class="row.workflow_status === 'validated' ? 'is-ok' : 'is-pending'"
                            >
                                {{ row.workflow_status_fr }}
                            </span>
                        </td>
                        <td class="rcg-actions">
                            <div v-if="rowHasActions(row)" class="rcg-menu">
                                <button
                                    type="button"
                                    class="rcg-menu-trigger"
                                    :aria-expanded="openMenuId === row.id"
                                    aria-haspopup="menu"
                                    title="Actions"
                                    @click.stop="toggleMenu(row, $event)"
                                >
                                    <span class="rcg-menu-dots" aria-hidden="true">⋯</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div
                v-if="openMenuRow"
                ref="menuPanelRef"
                class="rcg-menu-panel"
                role="menu"
                :style="menuPanelStyle"
                @click.stop
            >
                <button
                    v-if="permissions.can_edit"
                    type="button"
                    class="rcg-menu-item"
                    role="menuitem"
                    @click="runMenuAction(() => openEdit(openMenuRow))"
                >
                    Modifier
                </button>
                <button
                    v-if="permissions.can_validate && openMenuRow.workflow_status !== 'validated'"
                    type="button"
                    class="rcg-menu-item rcg-menu-item-validate"
                    role="menuitem"
                    :disabled="busyId === openMenuRow.id"
                    @click="runMenuAction(() => validateRow(openMenuRow))"
                >
                    Valider
                </button>
                <button
                    v-if="permissions.can_delete"
                    type="button"
                    class="rcg-menu-item rcg-menu-item-danger"
                    role="menuitem"
                    :disabled="busyId === openMenuRow.id"
                    @click="runMenuAction(() => removeRow(openMenuRow))"
                >
                    Supprimer
                </button>
            </div>
        </Teleport>

        <div v-if="modalOpen" class="rcg-modal-backdrop" @click.self="closeModal">
            <div class="rcg-modal" role="dialog" aria-modal="true">
                <div class="rcg-modal-header">
                    <h2>{{ editingId ? 'Modifier le compte' : 'Nouvelle ligne' }}</h2>
                    <button type="button" class="rcg-modal-close" @click="closeModal">×</button>
                </div>

                <form class="rcg-form" @submit.prevent="saveRow">
                    <div class="rcg-form-grid">
                        <label>
                            <span>USER_ID *</span>
                            <input v-model="form.user_id" required maxlength="120" />
                        </label>
                        <label>
                            <span>USER_NAME</span>
                            <input v-model="form.user_name" maxlength="255" />
                        </label>
                        <label>
                            <span>Statut</span>
                            <select v-model="form.statut">
                                <option value="">—</option>
                                <option value="active">active</option>
                                <option value="desactive">desactive</option>
                            </select>
                        </label>
                        <label>
                            <span>Forgotten</span>
                            <select v-model="form.forgotten">
                                <option value="">—</option>
                                <option value="N">N</option>
                                <option value="Y">Y</option>
                            </select>
                        </label>
                        <label>
                            <span>Type de compte</span>
                            <select v-model="form.account_type">
                                <option value="">—</option>
                                <option value="Compte de service">Compte de service</option>
                                <option value="Compte applicatif">Compte applicatif</option>
                                <option value="Compte technique">Compte technique</option>
                                <option value="Compte système">Compte système</option>
                                <option value="Compte partagé">Compte partagé</option>
                                <option value="Compte administrateur">Compte administrateur</option>
                            </select>
                        </label>
                        <label>
                            <span>Système / Application</span>
                            <input v-model="form.system_application" />
                        </label>
                        <label class="rcg-span-2">
                            <span>Utilité / Finalité</span>
                            <textarea v-model="form.purpose" rows="2" />
                        </label>
                        <label>
                            <span>Owner / Responsable</span>
                            <input v-model="form.owner" />
                        </label>
                        <label>
                            <span>Utilisation</span>
                            <input v-model="form.usage" />
                        </label>
                        <label>
                            <span>Privilèges / Rôle</span>
                            <input v-model="form.privileges_role" />
                        </label>
                        <label>
                            <span>Compte nominatif associé</span>
                            <input v-model="form.associated_nominative_account" />
                        </label>
                        <label>
                            <span>Dernière revue</span>
                            <input v-model="form.last_review_date" type="date" />
                        </label>
                        <label>
                            <span>Mode d’utilisation</span>
                            <select v-model="form.usage_mode">
                                <option value="">—</option>
                                <option value="Manuel">Manuel</option>
                                <option value="Automatique">Automatique</option>
                            </select>
                        </label>
                        <label>
                            <span>Accès interactif</span>
                            <select v-model="form.interactive_access">
                                <option value="">—</option>
                                <option value="Oui">Oui</option>
                                <option value="Non">Non</option>
                            </select>
                        </label>
                        <label>
                            <span>Mot de passe géré par</span>
                            <input v-model="form.password_managed_by" />
                        </label>
                        <label>
                            <span>MFA</span>
                            <select v-model="form.mfa">
                                <option value="">—</option>
                                <option value="Oui">Oui</option>
                                <option value="Non">Non</option>
                                <option value="N/A">N/A</option>
                                <option value="À confirmer">À confirmer</option>
                            </select>
                        </label>
                        <label>
                            <span>Journalisation</span>
                            <select v-model="form.logging_enabled">
                                <option value="">—</option>
                                <option value="Oui">Oui</option>
                                <option value="Non">Non</option>
                            </select>
                        </label>
                        <label>
                            <span>Revue périodique</span>
                            <select v-model="form.periodic_review">
                                <option value="">—</option>
                                <option value="Oui">Oui</option>
                                <option value="Non">Non</option>
                            </select>
                        </label>
                        <label>
                            <span>Risque</span>
                            <select v-model="form.risk">
                                <option value="">—</option>
                                <option value="Élevé">Élevé</option>
                                <option value="Moyen">Moyen</option>
                                <option value="Faible">Faible</option>
                            </select>
                        </label>
                        <label class="rcg-span-2">
                            <span>Justification de l’existence</span>
                            <textarea v-model="form.existence_justification" rows="2" />
                        </label>
                        <label class="rcg-span-2">
                            <span>Action / Observation</span>
                            <textarea v-model="form.action_observation" rows="2" />
                        </label>
                        <label class="rcg-span-2">
                            <span>Mesure corrective</span>
                            <textarea v-model="form.corrective_measure" rows="2" />
                        </label>
                    </div>

                    <p class="rcg-form-note">
                        Après enregistrement, la ligne passe en <strong>attente de validation</strong> Responsable IT.
                    </p>

                    <div class="rcg-modal-actions">
                        <button type="button" class="rcg-btn" @click="closeModal">Annuler</button>
                        <button type="submit" class="rcg-btn rcg-btn-primary" :disabled="saving">
                            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';
import api from '../../api/client';

const loading = ref(true);
const saving = ref(false);
const busyId = ref(null);
const error = ref('');
const success = ref('');
const rows = ref([]);
const filiales = ref([]);
const selectedEnvironmentId = ref(null);
const canSelectFiliale = ref(false);
const permissions = reactive({
    can_create: false,
    can_edit: false,
    can_validate: false,
    can_delete: false,
});

const modalOpen = ref(false);
const editingId = ref(null);
const form = reactive(emptyForm());

const openMenuRow = ref(null);
const openMenuId = ref(null);
const menuPanelRef = ref(null);
const menuPanelStyle = ref({ top: '0px', left: '0px' });

function rowHasActions(row) {
    return permissions.can_edit
        || permissions.can_delete
        || (permissions.can_validate && row.workflow_status !== 'validated');
}

async function positionMenu(trigger) {
    await nextTick();
    const panel = menuPanelRef.value;
    if (!panel || !trigger) return;

    const rect = trigger.getBoundingClientRect();
    const panelWidth = panel.offsetWidth;
    const panelHeight = panel.offsetHeight;
    const margin = 8;

    let top = rect.bottom + 4;
    let left = rect.right - panelWidth;

    if (top + panelHeight > window.innerHeight - margin) {
        top = rect.top - panelHeight - 4;
    }
    if (top < margin) top = margin;
    if (left < margin) left = margin;
    if (left + panelWidth > window.innerWidth - margin) {
        left = window.innerWidth - panelWidth - margin;
    }

    menuPanelStyle.value = { top: `${top}px`, left: `${left}px` };
}

async function toggleMenu(row, event) {
    if (openMenuId.value === row.id) {
        closeMenu();
        return;
    }

    openMenuRow.value = row;
    openMenuId.value = row.id;
    await positionMenu(event.currentTarget);
}

function closeMenu() {
    openMenuRow.value = null;
    openMenuId.value = null;
}

function runMenuAction(action) {
    closeMenu();
    action();
}

function onDocumentClick(event) {
    if (!openMenuId.value) return;
    const panel = menuPanelRef.value;
    if (panel && panel.contains(event.target)) return;
    closeMenu();
}

function onViewportChange() {
    if (openMenuId.value) closeMenu();
}

function emptyForm() {
    return {
        user_id: '',
        user_name: '',
        statut: '',
        forgotten: '',
        account_type: '',
        purpose: '',
        system_application: '',
        owner: '',
        usage: '',
        privileges_role: '',
        associated_nominative_account: '',
        last_review_date: '',
        action_observation: '',
        existence_justification: '',
        usage_mode: '',
        interactive_access: '',
        password_managed_by: '',
        mfa: '',
        logging_enabled: '',
        periodic_review: '',
        risk: '',
        corrective_measure: '',
    };
}

function formatDate(value) {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    if (!y || !m || !d) return value;
    return `${d}/${m}/${y}`;
}

function riskClass(risk) {
    if (risk === 'Élevé') return 'is-high';
    if (risk === 'Moyen') return 'is-medium';
    if (risk === 'Faible') return 'is-low';
    return '';
}

function applyPermissions(payload) {
    const next = payload?.permissions ?? {};
    permissions.can_create = Boolean(next.can_create);
    permissions.can_edit = Boolean(next.can_edit);
    permissions.can_validate = Boolean(next.can_validate);
    permissions.can_delete = Boolean(next.can_delete);
}

async function loadRows() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/generic-accounts', {
            params: selectedEnvironmentId.value
                ? { environment_id: selectedEnvironmentId.value }
                : {},
        });
        const payload = data?.data ?? data;
        rows.value = payload?.rows ?? [];
        filiales.value = payload?.filiales ?? [];
        canSelectFiliale.value = Boolean(payload?.can_select_filiale);
        if (payload?.environment_id) {
            selectedEnvironmentId.value = payload.environment_id;
        } else if (!selectedEnvironmentId.value && filiales.value.length) {
            selectedEnvironmentId.value = filiales.value[0].id;
        }
        applyPermissions(payload);
    } catch (err) {
        error.value = err.response?.data?.errors?.auth?.[0]
            || err.response?.data?.message
            || 'Impossible de charger le registre.';
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingId.value = null;
    Object.assign(form, emptyForm());
    modalOpen.value = true;
    success.value = '';
    error.value = '';
}

function openEdit(row) {
    editingId.value = row.id;
    Object.assign(form, emptyForm(), {
        user_id: row.user_id ?? '',
        user_name: row.user_name ?? '',
        statut: row.statut ?? '',
        forgotten: row.forgotten ?? '',
        account_type: row.account_type ?? '',
        purpose: row.purpose ?? '',
        system_application: row.system_application ?? '',
        owner: row.owner ?? '',
        usage: row.usage ?? '',
        privileges_role: row.privileges_role ?? '',
        associated_nominative_account: row.associated_nominative_account ?? '',
        last_review_date: row.last_review_date ?? '',
        action_observation: row.action_observation ?? '',
        existence_justification: row.existence_justification ?? '',
        usage_mode: row.usage_mode ?? '',
        interactive_access: row.interactive_access ?? '',
        password_managed_by: row.password_managed_by ?? '',
        mfa: row.mfa ?? '',
        logging_enabled: row.logging_enabled ?? '',
        periodic_review: row.periodic_review ?? '',
        risk: row.risk ?? '',
        corrective_measure: row.corrective_measure ?? '',
    });
    modalOpen.value = true;
    success.value = '';
    error.value = '';
}

function closeModal() {
    modalOpen.value = false;
    editingId.value = null;
}

async function saveRow() {
    saving.value = true;
    error.value = '';
    success.value = '';

    const payload = {
        ...form,
        environment_id: selectedEnvironmentId.value || null,
        last_review_date: form.last_review_date || null,
    };

    try {
        if (editingId.value) {
            await api.put(`/generic-accounts/${editingId.value}`, payload);
            success.value = 'Modification enregistrée — en attente de validation Responsable IT.';
        } else {
            await api.post('/generic-accounts', payload);
            success.value = 'Ligne créée — en attente de validation Responsable IT.';
        }
        closeModal();
        await loadRows();
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'Erreur lors de l’enregistrement.';
    } finally {
        saving.value = false;
    }
}

async function validateRow(row) {
    busyId.value = row.id;
    error.value = '';
    success.value = '';

    try {
        await api.post(`/generic-accounts/${row.id}/validate`);
        success.value = `Compte ${row.user_id} validé.`;
        await loadRows();
    } catch (err) {
        error.value = err.response?.data?.errors?.auth?.[0]
            || err.response?.data?.errors?.workflow_status?.[0]
            || 'Validation impossible.';
    } finally {
        busyId.value = null;
    }
}

async function removeRow(row) {
    if (!window.confirm(`Supprimer le compte ${row.user_id} ?`)) {
        return;
    }

    busyId.value = row.id;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/generic-accounts/${row.id}`);
        success.value = 'Ligne supprimée.';
        await loadRows();
    } catch (err) {
        error.value = err.response?.data?.errors?.auth?.[0] || 'Suppression impossible.';
    } finally {
        busyId.value = null;
    }
}

onMounted(() => {
    loadRows();
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
});
</script>

<style scoped>
.rcg-page {
    width: 100%;
    min-height: 100%;
    padding: 1.25rem 1.5rem 2rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.rcg-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.rcg-header-main {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
}

.rcg-back {
    width: fit-content;
    font-size: 0.8125rem;
    color: #64748b;
}

.rcg-back:hover { color: #0f172a; }

.rcg-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
}

.rcg-hint {
    margin: 0;
    max-width: 42rem;
    font-size: 0.875rem;
    color: #64748b;
}

.rcg-header-actions {
    display: flex;
    align-items: flex-end;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.rcg-env {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
}

.rcg-env select {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.45rem 0.65rem;
    font-size: 0.8125rem;
    text-transform: none;
    font-weight: 500;
    background: #fff;
}

.rcg-btn {
    border: 1px solid #cbd5e1;
    border-radius: 0.55rem;
    background: #fff;
    padding: 0.5rem 0.85rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.rcg-btn-primary {
    border-color: #c00000;
    background: #c00000;
    color: #fff;
}

.rcg-btn-primary:hover { background: #9f0000; }
.rcg-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.rcg-error,
.rcg-success {
    border-radius: 0.55rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

.rcg-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.rcg-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

.rcg-loading {
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.rcg-table-wrap {
    width: 100%;
    overflow: auto;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

.rcg-table {
    width: 100%;
    min-width: 110rem;
    border-collapse: collapse;
    font-size: 0.75rem;
    line-height: 1.35;
    color: #0f172a;
}

.rcg-table th,
.rcg-table td {
    border: 1px solid #cbd5e1;
    padding: 0.5rem 0.55rem;
    vertical-align: top;
    word-break: break-word;
}

.rcg-banner {
    background: #c00000;
    color: #fff;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-size: 0.82rem;
}

.rcg-table thead tr:last-child th {
    background: #c00000;
    color: #fff;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    font-size: 0.65rem;
    letter-spacing: 0.03em;
    white-space: nowrap;
}

.rcg-strong { font-weight: 700; }
.rcg-center { text-align: center; }
.rcg-empty { text-align: center; color: #64748b; padding: 2rem !important; }

.rcg-status {
    display: inline-flex;
    padding: 0.2rem 0.45rem;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    white-space: nowrap;
}

.rcg-status.is-pending { background: #fff7ed; color: #c2410c; }
.rcg-status.is-ok { background: #ecfdf5; color: #047857; }

.rcg-risk {
    display: inline-block;
    min-width: 3.5rem;
    padding: 0.15rem 0.4rem;
    border-radius: 0.35rem;
    font-weight: 700;
}

.rcg-risk.is-high { background: #fee2e2; color: #b91c1c; }
.rcg-risk.is-medium { background: #ffedd5; color: #c2410c; }
.rcg-risk.is-low { background: #dcfce7; color: #15803d; }

.rcg-actions {
    white-space: nowrap;
    min-width: 3.5rem;
    text-align: center;
    vertical-align: middle !important;
}

.rcg-menu {
    display: flex;
    justify-content: center;
}

.rcg-menu-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.25rem;
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}

.rcg-menu-trigger:hover,
.rcg-menu-trigger[aria-expanded='true'] {
    background: #f8fafc;
    border-color: #94a3b8;
}

.rcg-menu-dots {
    font-size: 1.1rem;
    line-height: 1;
    letter-spacing: -0.05em;
    transform: translateY(-1px);
}

.rcg-menu-panel {
    position: fixed;
    z-index: 9999;
    min-width: 10.5rem;
    padding: 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #ffffff;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
}

.rcg-menu-item {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.45rem 0.65rem;
    border: none;
    border-radius: 0.375rem;
    background: transparent;
    color: #334155;
    font-size: 0.78rem;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
    white-space: nowrap;
}

.rcg-menu-item:hover {
    background: #f8fafc;
}

.rcg-menu-item-validate {
    color: #047857;
}

.rcg-menu-item-validate:hover {
    background: #ecfdf5;
}

.rcg-menu-item-danger {
    color: #b91c1c;
}

.rcg-menu-item-danger:hover {
    background: #fef2f2;
}

.rcg-menu-item:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rcg-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.rcg-modal {
    width: min(56rem, 100%);
    max-height: min(92vh, 56rem);
    overflow: auto;
    background: #fff;
    border-radius: 0.85rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
}

.rcg-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.rcg-modal-header h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.rcg-modal-close {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}

.rcg-form { padding: 1rem 1.25rem 1.25rem; }

.rcg-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem 1rem;
}

.rcg-form-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.rcg-form-grid input,
.rcg-form-grid select,
.rcg-form-grid textarea {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: none;
    color: #0f172a;
    background: #fff;
}

.rcg-span-2 { grid-column: span 2; }

.rcg-form-note {
    margin: 1rem 0 0;
    font-size: 0.8125rem;
    color: #64748b;
}

.rcg-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.65rem;
    margin-top: 1rem;
}

@media (max-width: 720px) {
    .rcg-form-grid { grid-template-columns: 1fr; }
    .rcg-span-2 { grid-column: span 1; }
}
</style>
