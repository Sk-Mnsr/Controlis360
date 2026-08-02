<template>
    <div class="mission-create-page">
        <header class="mission-create-topbar">
            <div>
                <RouterLink :to="{ name: 'audit.missions' }" class="mission-create-back">
                    ← Retour aux missions
                </RouterLink>
                <h1 class="mission-create-title">Nouvelle mission</h1>
                <p class="mission-create-subtitle">
                    Définissez le périmètre, le calendrier et les pièces jointes de la mission.
                </p>
            </div>
        </header>

        <form id="mission-form" class="mission-create-form" @submit.prevent="submit">
            <section class="mission-create-card">
                <div class="mission-create-card-head">
                    <span class="mission-create-step">1</span>
                    <div>
                        <h2 class="mission-create-card-title">Identification</h2>
                        <p class="mission-create-card-desc">Référence, type et filiale concernés</p>
                    </div>
                </div>

                <div class="mission-create-grid mission-create-grid-3">
                    <div class="mission-field">
                        <label class="mission-label">Référence mission</label>
                        <input
                            v-model="form.reference"
                            type="text"
                            required
                            class="mission-input"
                            placeholder="Ex. MIS-2026-0010"
                        />
                    </div>

                    <div class="mission-field">
                        <label class="mission-label">Type de mission</label>
                        <select v-model="form.mission_type" required class="mission-input">
                            <option value="" disabled>Sélectionner un type</option>
                            <option
                                v-for="type in availableMissionTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div class="mission-field">
                        <label class="mission-label">Environnement concerné</label>
                        <select
                            v-model="form.environment_id"
                            required
                            class="mission-input"
                            @change="onEnvironmentChange"
                        >
                            <option value="" disabled>Sélectionner un environnement</option>
                            <option
                                v-for="environment in environments"
                                :key="environment.id"
                                :value="environment.id"
                            >
                                {{ environment.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="mission-create-card">
                <div class="mission-create-card-head">
                    <span class="mission-create-step">2</span>
                    <div>
                        <h2 class="mission-create-card-title">Périmètre</h2>
                        <p class="mission-create-card-desc">
                            Entités concernées (départements et agences) et responsables associés
                        </p>
                    </div>
                </div>

                <div class="mission-create-grid mission-create-grid-2">
                    <div class="mission-field">
                        <label class="mission-label">
                            Entité(s) concernée(s)
                            <span v-if="form.entity_ids.length" class="mission-create-count">
                                {{ form.entity_ids.length }}
                            </span>
                        </label>
                        <MultiSelectDropdown
                            v-model="form.entity_ids"
                            :options="departmentSelectOptions"
                            :placeholder="entityPlaceholder"
                            empty-text="Aucune entité disponible"
                            :disabled="!form.environment_id || entitiesLoading"
                            trigger-class="mission-input"
                            @change="entitySelectionError = false"
                        />
                        <p v-if="entitySelectionError" class="mission-create-field-error">
                            Sélectionnez au moins une entité
                        </p>
                        <p v-else-if="entitiesLoading" class="mission-create-hint">
                            Chargement des entités...
                        </p>
                        <p v-else-if="form.environment_id" class="mission-create-hint">
                            {{ departmentCount }} département(s) · {{ agencyCount }} agence(s)
                        </p>
                    </div>

                    <div class="mission-field">
                        <label class="mission-label">
                            Missionnaire
                            <span v-if="missionnaires.length" class="mission-create-count">
                                {{ missionnaires.length }}
                            </span>
                        </label>
                        <button
                            type="button"
                            class="mission-create-responsible"
                            :class="{ filled: missionnairesSummary }"
                            @click="openMissionnairesModal"
                        >
                            {{ missionnairesSummary || 'Cliquer pour renseigner les missionnaires' }}
                        </button>
                        <p class="mission-create-hint">
                            Nom, e-mail, téléphone, poste, entité (interne/externe) et rôle (responsable ou membre)
                        </p>
                    </div>
                </div>
            </section>

            <section class="mission-create-card">
                <div class="mission-create-card-head">
                    <span class="mission-create-step">3</span>
                    <div>
                        <h2 class="mission-create-card-title">Calendrier</h2>
                        <p class="mission-create-card-desc">Dates d’émission, de début et de fin</p>
                    </div>
                </div>

                <div class="mission-create-grid mission-create-grid-3">
                    <div class="mission-field">
                        <label class="mission-label">Date émise</label>
                        <input v-model="form.issue_date" type="date" required class="mission-input" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Date début</label>
                        <input v-model="form.start_date" type="date" required class="mission-input" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Date fin</label>
                        <input v-model="form.end_date" type="date" class="mission-input" />
                    </div>
                </div>
            </section>

            <section class="mission-create-card">
                <div class="mission-create-card-head">
                    <span class="mission-create-step">4</span>
                    <div>
                        <h2 class="mission-create-card-title">Documents</h2>
                        <p class="mission-create-card-desc">Rapport(s) associé(s) à la mission</p>
                    </div>
                </div>

                <div class="mission-create-attachments">
                    <div
                        v-for="(slot, index) in reportSlots"
                        :key="slot.key"
                        class="mission-create-attachment"
                    >
                        <div class="mission-create-attachment-main">
                            <span class="mission-create-attachment-icon" aria-hidden="true">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                    <path d="M4.75 2A2.75 2.75 0 002 4.75v10.5A2.75 2.75 0 004.75 18h6.086a2.75 2.75 0 001.944-.805l3.415-3.415A2.75 2.75 0 0017 11.836V4.75A2.75 2.75 0 0014.25 2H4.75zM14.5 11.25H12a.75.75 0 01-.75-.75V8H14.5v3.25z" />
                                </svg>
                            </span>
                            <div class="mission-create-attachment-meta">
                                <p class="mission-create-attachment-label">
                                    {{ slot.file?.name || `Pièce jointe ${index + 1}` }}
                                </p>
                                <p class="mission-create-attachment-hint">
                                    {{ slot.file ? formatFileSize(slot.file.size) : 'Aucun fichier sélectionné' }}
                                </p>
                            </div>
                        </div>
                        <div class="mission-create-attachment-actions">
                            <label class="mission-create-file-btn">
                                {{ slot.file ? 'Remplacer' : 'Choisir' }}
                                <input
                                    type="file"
                                    class="sr-only"
                                    @change="onReportSelected(index, $event)"
                                />
                            </label>
                            <button
                                v-if="reportSlots.length > 1"
                                type="button"
                                class="mission-create-remove-btn"
                                @click="removeReportSlot(index)"
                            >
                                Retirer
                            </button>
                        </div>
                    </div>

                    <button type="button" class="mission-create-add-file" @click="addReportSlot">
                        + Ajouter une pièce jointe
                    </button>
                </div>
            </section>

            <p v-if="error" class="mission-create-alert mission-create-alert-error">{{ error }}</p>
            <p v-if="warning" class="mission-create-alert mission-create-alert-warning">{{ warning }}</p>
            <p v-if="message" class="mission-create-alert mission-create-alert-success">{{ message }}</p>

            <footer class="mission-create-footer">
                <button
                    type="button"
                    class="mission-create-btn mission-create-btn-ghost"
                    :disabled="saving"
                    @click="cancel"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    class="mission-create-btn mission-create-btn-primary"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer la mission' }}
                </button>
            </footer>
        </form>

        <MissionnairesModal
            v-model="missionnaires"
            v-model:open="missionnairesModalOpen"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import MissionnairesModal from '../../components/audit/MissionnairesModal.vue';
import MultiSelectDropdown from '../../components/MultiSelectDropdown.vue';
import { useMissionTypes } from '../../composables/useMissionTypes';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const { loadMissionTypes, getTypesForProfile } = useMissionTypes();

const environments = ref([]);
const entities = ref([]);
const entitiesLoading = ref(false);
const saving = ref(false);
const message = ref('');
const warning = ref('');
const error = ref('');
const entitySelectionError = ref(false);
const missionnaires = ref([]);
const missionnairesModalOpen = ref(false);

const form = reactive({
    reference: '',
    mission_type: '',
    environment_id: '',
    entity_ids: [],
    issue_date: todayIso(),
    start_date: '',
    end_date: '',
});

const reportSlots = ref([{ key: 1, file: null }]);
let reportSlotKey = 1;

function todayIso() {
    const now = new Date();
    const offsetMs = now.getTimezoneOffset() * 60 * 1000;
    return new Date(now.getTime() - offsetMs).toISOString().slice(0, 10);
}

const departmentSelectOptions = computed(() => {
    const departments = entities.value
        .filter((entity) => entity.type === 'department')
        .map((entity) => ({
            id: entity.id,
            name: entityLabel(entity),
            group: 'Départements',
        }));

    const agencies = entities.value
        .filter((entity) => entity.type === 'agency')
        .map((entity) => ({
            id: entity.id,
            name: entityLabel(entity),
            group: 'Agences',
        }));

    return [...departments, ...agencies];
});

const departmentCount = computed(() =>
    entities.value.filter((entity) => entity.type === 'department').length,
);

const agencyCount = computed(() =>
    entities.value.filter((entity) => entity.type === 'agency').length,
);

const entityPlaceholder = computed(() => {
    if (!form.environment_id) {
        return 'Sélectionnez d’abord un environnement';
    }

    if (entitiesLoading.value) {
        return 'Chargement...';
    }

    return 'Sélectionner une ou plusieurs entités';
});

const missionnairesSummary = computed(() => {
    if (!missionnaires.value.length) return '';
    return missionnaires.value
        .map((m) => {
            const role = m.responsable_equipe === 'responsable' ? 'responsable' : 'membre';
            return m.nom ? `${m.nom} (${role})` : '';
        })
        .filter(Boolean)
        .join(', ');
});

const availableMissionTypes = computed(() => (
    getTypesForProfile(auth.user?.profile ?? '')
));

function openMissionnairesModal() {
    missionnairesModalOpen.value = true;
}

function extractList(data) {
    if (Array.isArray(data?.data?.data)) return data.data.data;
    if (Array.isArray(data?.data)) return data.data;
    if (Array.isArray(data)) return data;
    return [];
}

function entityLabel(entity) {
    return entity.name ?? '';
}

function dedupeEntities(items) {
    const byId = new Map();
    for (const item of items) {
        if (item?.id != null) {
            byId.set(Number(item.id), item);
        }
    }
    return [...byId.values()];
}

function formatFileSize(bytes) {
    if (!bytes && bytes !== 0) return '';
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
}

async function onEnvironmentChange() {
    form.entity_ids = [];
    entitySelectionError.value = false;
    await loadEntities();
}

function addReportSlot() {
    reportSlotKey += 1;
    reportSlots.value.push({ key: reportSlotKey, file: null });
}

function removeReportSlot(index) {
    reportSlots.value.splice(index, 1);
}

function onReportSelected(index, event) {
    reportSlots.value[index].file = event.target.files?.[0] ?? null;
}

function selectedReportFiles() {
    return reportSlots.value.map((slot) => slot.file).filter(Boolean);
}

function buildFormData() {
    const fd = new FormData();
    const append = (key, value) => {
        if (value !== null && value !== undefined && value !== '') {
            fd.append(key, value);
        }
    };

    append('reference', form.reference.trim());
    append('mission_type', form.mission_type);
    append('environment_id', form.environment_id);
    form.entity_ids.forEach((id) => fd.append('entity_ids[]', String(id)));
    append('auditor', auth.user?.name ?? '');
    append('issue_date', form.issue_date);
    append('start_date', form.start_date);
    append('end_date', form.end_date);
    fd.append('missionnaires', JSON.stringify(missionnaires.value ?? []));
    selectedReportFiles().forEach((file) => fd.append('report_attachments[]', file));

    return fd;
}

async function loadEnvironments() {
    const { data } = await api.get('/referentials/mission-environments');
    environments.value = extractList(data);
}

async function loadEntities() {
    if (!form.environment_id) {
        entities.value = [];
        return;
    }

    entitiesLoading.value = true;
    try {
        const { data } = await api.get('/referentials/entities-departments', {
            params: { environment_id: form.environment_id },
        });
        entities.value = dedupeEntities(extractList(data));
    } finally {
        entitiesLoading.value = false;
    }
}

function cancel() {
    router.push({ name: 'audit.missions' });
}

function extractSubmitError(err) {
    const payload = err.response?.data;
    const errors = payload?.errors ?? payload?.data;

    if (errors && typeof errors === 'object' && !Array.isArray(errors)) {
        const messages = Object.values(errors).flat().filter(Boolean);

        if (messages.length) {
            return messages.join(' ');
        }
    }

    if (Array.isArray(payload?.message)) {
        return payload.message.join(' ');
    }

    if (typeof payload?.message === 'string' && payload.message.trim()) {
        return payload.message;
    }

    if (!err.response) {
        return 'Le serveur est inaccessible. Vérifiez votre connexion puis réessayez.';
    }

    return `La mission n’a pas pu être enregistrée (erreur ${err.response.status}). Réessayez ou contactez l’administrateur.`;
}

async function submit() {
    if (!form.environment_id) {
        error.value = 'Sélectionnez un environnement existant.';
        return;
    }

    if (!form.entity_ids.length) {
        entitySelectionError.value = true;
        return;
    }

    saving.value = true;
    message.value = '';
    warning.value = '';
    error.value = '';

    try {
        const { data } = await api.post('/missions', buildFormData(), {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        const result = data?.data ?? data;

        if (result?.warning) {
            warning.value = result.warning;
            setTimeout(() => router.push({ name: 'audit.missions' }), 2500);
            return;
        }

        message.value = 'Mission créée. Ajoutez une recommandation depuis le détail pour notifier le responsable.';
        setTimeout(() => router.push({ name: 'audit.missions' }), 1200);
    } catch (err) {
        error.value = extractSubmitError(err);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadMissionTypes();
    await loadEnvironments();
});
</script>

<style scoped>
.mission-create-page {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-width: 72rem;
    margin: 0 auto;
}

.mission-create-topbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.mission-create-back {
    display: inline-block;
    margin-bottom: 0.4rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
}

.mission-create-back:hover {
    color: #0f172a;
}

.mission-create-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

.mission-create-subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
}

.mission-create-footer {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    justify-content: flex-end;
    padding-top: 0.25rem;
}

.mission-create-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mission-create-card {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #ffffff;
    padding: 1.25rem 1.35rem 1.4rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.mission-create-card-head {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    margin-bottom: 1.15rem;
}

.mission-create-step {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-size: 0.8125rem;
    font-weight: 700;
    flex-shrink: 0;
}

.mission-create-card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.mission-create-card-desc {
    margin: 0.2rem 0 0;
    font-size: 0.8125rem;
    color: #64748b;
}

.mission-create-grid {
    display: grid;
    gap: 1rem;
}

.mission-create-grid-2 {
    grid-template-columns: 1fr;
}

.mission-create-grid-3 {
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .mission-create-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mission-create-grid-3 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .mission-create-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.mission-field {
    min-width: 0;
}

.mission-label {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.4rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
}

.mission-create-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: #047857;
    color: #ffffff;
    font-size: 0.6875rem;
    font-weight: 700;
}

.mission-input {
    width: 100%;
    border-radius: 0.65rem;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    padding: 0.65rem 0.8rem;
    font-size: 0.875rem;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.mission-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.mission-create-responsible {
    display: block;
    width: 100%;
    min-height: 2.65rem;
    border-radius: 0.65rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 0.65rem 0.8rem;
    font-size: 0.875rem;
    color: #94a3b8;
    line-height: 1.4;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.mission-create-responsible:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}

.mission-create-responsible.filled {
    color: #0f172a;
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.mission-create-hint {
    margin: 0.4rem 0 0;
    font-size: 0.75rem;
    color: #64748b;
}

.mission-create-field-error {
    margin: 0.4rem 0 0;
    font-size: 0.75rem;
    color: #dc2626;
}

.mission-create-attachments {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mission-create-attachment {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    background: #f8fafc;
    padding: 0.85rem 1rem;
}

.mission-create-attachment-main {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.mission-create-attachment-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.65rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #047857;
}

.mission-create-attachment-label {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 18rem;
}

.mission-create-attachment-hint {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: #64748b;
}

.mission-create-attachment-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mission-create-file-btn {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.4rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}

.mission-create-file-btn:hover {
    background: #f1f5f9;
}

.mission-create-remove-btn {
    border: none;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
}

.mission-create-remove-btn:hover {
    color: #dc2626;
}

.mission-create-add-file {
    align-self: flex-start;
    border: 1px dashed #86efac;
    border-radius: 0.65rem;
    background: #ecfdf5;
    padding: 0.55rem 0.85rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #047857;
    cursor: pointer;
}

.mission-create-add-file:hover {
    background: #d1fae5;
}

.mission-create-btn {
    border-radius: 0.65rem;
    padding: 0.65rem 1.1rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, opacity 0.15s;
}

.mission-create-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.mission-create-btn-primary {
    border: none;
    background: #047857;
    color: #ffffff;
}

.mission-create-btn-primary:hover:not(:disabled) {
    background: #065f46;
}

.mission-create-btn-ghost {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
}

.mission-create-btn-ghost:hover:not(:disabled) {
    background: #f8fafc;
}

.mission-create-alert {
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

.mission-create-alert-error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.mission-create-alert-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.mission-create-alert-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
