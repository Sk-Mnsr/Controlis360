<template>
    <div class="saisie-page">
        <header class="saisie-header">
            <h1 class="saisie-title">Saisie des risques opérationnels</h1>
            <p class="saisie-subtitle">
                Ajoutez une ligne d'analyse et affectez-la à l'entité concernée (département ou agence).
            </p>
        </header>

        <div v-if="loading" class="saisie-loading">Chargement...</div>
        <p v-else-if="error" class="saisie-error">{{ error }}</p>

        <form v-else class="saisie-form" @submit.prevent="submit(false)">
            <section class="saisie-section">
                <h2 class="saisie-section-title">Entité concernée</h2>
                <label class="saisie-field">
                    <span class="saisie-label">Entité</span>
                    <select v-model="selectedEntityId" required class="saisie-select" @change="onEntityChange">
                        <option value="">— Sélectionner une entité —</option>
                        <optgroup v-if="departmentEntities.length" label="Départements">
                            <option v-for="entity in departmentEntities" :key="entity.id" :value="entity.id">
                                {{ entityOptionLabel(entity) }}
                            </option>
                        </optgroup>
                        <optgroup v-if="agencyEntities.length" label="Agences">
                            <option v-for="entity in agencyEntities" :key="entity.id" :value="entity.id">
                                {{ entityOptionLabel(entity) }}
                            </option>
                        </optgroup>
                    </select>
                    <p v-if="!entities.length" class="saisie-empty">Aucune entité disponible pour votre environnement.</p>
                </label>
            </section>

            <section v-if="selectedEntityId" class="saisie-section">
                <h2 class="saisie-section-title">Type de saisie</h2>
                <div class="saisie-mode">
                    <label class="saisie-mode-option">
                        <input v-model="saisieMode" type="radio" value="new" />
                        Nouveau sous-processus
                    </label>
                    <label class="saisie-mode-option" :class="{ disabled: !existingGroups.length }">
                        <input
                            v-model="saisieMode"
                            type="radio"
                            value="existing"
                            :disabled="!existingGroups.length"
                        />
                        Risque sur sous-processus existant
                    </label>
                </div>

                <label v-if="saisieMode === 'existing'" class="saisie-field">
                    <span class="saisie-label">Sous-processus existant</span>
                    <select v-model="selectedGroupKey" required class="saisie-select" @change="applyExistingGroup">
                        <option value="">— Sélectionner —</option>
                        <option v-for="group in existingGroups" :key="group.key" :value="group.key">
                            N°{{ group.process_number ?? '—' }} — {{ group.sub_process_name }}
                            ({{ group.exceptions.length }} exc.)
                        </option>
                    </select>
                </label>
            </section>

            <section v-if="selectedEntityId && (saisieMode === 'new' || selectedGroupKey)" class="saisie-section">
                <h2 class="saisie-section-title">Sous-processus</h2>
                <OperationalRiskSubProcessFields
                    v-model="subProcessForm"
                    :department-name="selectedEntityName"
                    :readonly="saisieMode === 'existing'"
                />
            </section>

            <section v-if="selectedEntityId && (saisieMode === 'new' || selectedGroupKey)" class="saisie-section">
                <h2 class="saisie-section-title">Risque</h2>
                <OperationalRiskExceptionFields
                    v-model="exceptionForm"
                    :risk-families="riskFamilies"
                    :risk-classifications="riskClassifications"
                />
            </section>

            <p v-if="success" class="saisie-success">{{ success }}</p>
            <p v-if="submitError" class="saisie-error">{{ submitError }}</p>

            <div v-if="selectedEntityId" class="risk-form-actions">
                <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">
                    {{ saving ? 'Enregistrement...' : 'Enregistrer la ligne' }}
                </button>
                <button
                    type="button"
                    class="risk-form-btn risk-form-btn-primary"
                    :disabled="saving"
                    @click="submit(true)"
                >
                    {{ saving ? 'Envoi...' : 'Enregistrer et envoyer' }}
                </button>
                <button type="button" class="risk-form-btn risk-form-btn-secondary" :disabled="saving" @click="resetExceptionFields">
                    Réinitialiser
                </button>
                <RouterLink
                    v-if="selectedEntity"
                    :to="{
                        name: 'cartographie.departement-analyse',
                        params: { code: selectedEntity.code },
                        query: entityRouteQuery(selectedEntity),
                    }"
                    class="risk-form-btn risk-form-btn-secondary saisie-link"
                >
                    Voir l'entité
                </RouterLink>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import OperationalRiskSubProcessFields from '../../components/cartographie/OperationalRiskSubProcessFields.vue';
import OperationalRiskExceptionFields from '../../components/cartographie/OperationalRiskExceptionFields.vue';
import { groupRowsBySubProcess, subProcessFieldsFromRow } from '../../utils/operationalRiskGroups';
import { emptyExceptionForm } from '../../utils/operationalRiskForms';
import { environmentQueryParams, entityRouteQuery } from '../../utils/entityEnvironment';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const auth = useAuthStore();

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const submitError = ref('');
const success = ref('');
const entities = ref([]);
const riskFamilies = ref([]);
const riskClassifications = ref([]);
const selectedEntityId = ref('');
const saisieMode = ref('new');
const selectedGroupKey = ref('');
const existingGroups = ref([]);
const subProcessForm = ref(emptySubProcess());
const exceptionForm = ref(emptyExceptionForm());

const selectedEntity = computed(() =>
    entities.value.find((item) => String(item.id) === String(selectedEntityId.value)) ?? null,
);

const selectedEntityCode = computed(() => selectedEntity.value?.code ?? '');

const selectedEntityName = computed(() => selectedEntity.value?.name ?? '');

const showEnvironmentInLabels = computed(() =>
    auth.user?.profile === 'super_admin' && !auth.user?.environment_id,
);

function entityOptionLabel(entity) {
    if (showEnvironmentInLabels.value && entity.environment?.code) {
        return `${entity.environment.code} — ${entity.name}`;
    }

    return entity.name;
}

const departmentEntities = computed(() =>
    entities.value.filter((entity) => !entity.type || entity.type === 'department'),
);

const agencyEntities = computed(() =>
    entities.value.filter((entity) => entity.type === 'agency'),
);

function emptySubProcess() {
    return { process_number: null, process_name: '', ratio: null, sub_process_name: '' };
}

function emptyException() {
    return emptyExceptionForm();
}

async function loadContext() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/saisie-risques-context');
        const root = data?.data ?? data;
        entities.value = root?.entities ?? [];
        riskFamilies.value = root?.risk_families ?? [];
        riskClassifications.value = root?.risk_classifications ?? [];
        await applyRouteQuery();
    } catch {
        error.value = 'Accès réservé au personnel du contrôle interne.';
    } finally {
        loading.value = false;
    }
}

async function applyRouteQuery() {
    const entityCode = route.query.entity;
    if (!entityCode || typeof entityCode !== 'string') {
        return;
    }

    const entity = entities.value.find((item) => {
        if (item.code !== entityCode) {
            return false;
        }

        const routeEnvironment = route.query.environment;
        if (routeEnvironment && item.environment?.code) {
            return item.environment.code === routeEnvironment;
        }

        return true;
    });

    if (!entity) {
        return;
    }

    selectedEntityId.value = String(entity.id);
    await loadExistingGroups();

    if (route.query.mode === 'existing' && typeof route.query.group === 'string') {
        const group = existingGroups.value.find((item) => item.key === route.query.group);
        if (group) {
            saisieMode.value = 'existing';
            selectedGroupKey.value = group.key;
            applyExistingGroup();
            return;
        }
    }

    subProcessForm.value = {
        ...emptySubProcess(),
        process_name: selectedEntityName.value,
    };
    exceptionForm.value = emptyException();
}

async function loadExistingGroups() {
    if (!selectedEntity.value) {
        existingGroups.value = [];
        return;
    }

    try {
        const { data } = await api.get(`/referentials/analyse-risques/${selectedEntity.value.code}`, {
            params: environmentQueryParams(selectedEntity.value, { include_drafts: 1 }),
        });
        const rows = data?.data?.rows ?? data?.rows ?? [];
        existingGroups.value = groupRowsBySubProcess(rows);
    } catch {
        existingGroups.value = [];
    }
}

async function onEntityChange() {
    selectedGroupKey.value = '';
    saisieMode.value = 'new';
    subProcessForm.value = {
        ...emptySubProcess(),
        process_name: selectedEntityName.value,
    };
    exceptionForm.value = emptyException();
    success.value = '';
    submitError.value = '';
    await loadExistingGroups();
}

function applyExistingGroup() {
    const group = existingGroups.value.find((item) => item.key === selectedGroupKey.value);
    if (!group?.exceptions?.[0]) {
        return;
    }

    subProcessForm.value = subProcessFieldsFromRow(group.exceptions[0]);
}

function resetExceptionFields() {
    exceptionForm.value = emptyException();
    success.value = '';
    submitError.value = '';
}

watch(saisieMode, (mode) => {
    if (mode === 'new') {
        selectedGroupKey.value = '';
        subProcessForm.value = {
            ...emptySubProcess(),
            process_name: selectedEntityName.value,
        };
    }
});

async function submit(andSend = false) {
    if (!selectedEntity.value) {
        return;
    }

    saving.value = true;
    submitError.value = '';
    success.value = '';

    try {
        const { data } = await api.post(
            `/operational-risk-rows/departments/${selectedEntity.value.code}`,
            {
                ...subProcessForm.value,
                ...exceptionForm.value,
            },
            {
                params: environmentQueryParams(selectedEntity.value),
            },
        );

        const row = data?.data?.row ?? data?.row;

        if (andSend && row?.id) {
            await api.post(`/operational-risk-rows/${row.id}/submit`);
        }

        const entityLabel = selectedEntityName.value;
        success.value = andSend
            ? `Ligne enregistrée et envoyée pour ${entityLabel}.`
            : `Ligne enregistrée pour ${entityLabel}.`;

        exceptionForm.value = emptyException();
        await loadExistingGroups();

        if (saisieMode.value === 'existing' && selectedGroupKey.value) {
            applyExistingGroup();
        } else {
            subProcessForm.value = {
                ...emptySubProcess(),
                process_name: selectedEntityName.value,
            };
        }
    } catch (err) {
        const messages = err.response?.data?.data ?? err.response?.data?.errors;
        if (typeof messages === 'object' && messages !== null) {
            submitError.value = Object.values(messages).flat().join(' ');
        } else {
            submitError.value = 'Impossible d\'enregistrer la ligne.';
        }
    } finally {
        saving.value = false;
    }
}

onMounted(loadContext);

watch(() => route.query, () => {
    if (!loading.value && entities.value.length) {
        applyRouteQuery();
    }
});
</script>

<style scoped>
@import '../../components/cartographie/risk-form-table.css';

.saisie-page {
    max-width: 72rem;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.saisie-header {
    border-bottom: 3px solid #c00000;
    padding-bottom: 0.75rem;
}

.saisie-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #111111;
}

.saisie-subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
}

.saisie-loading {
    color: #64748b;
    font-size: 0.875rem;
}

.saisie-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.saisie-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.saisie-section-title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #c00000;
}

.saisie-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    max-width: 28rem;
}

.saisie-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.saisie-select {
    border: 1px solid #111111;
    border-radius: 0.25rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
    background: #ffffff;
}

.saisie-mode {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.saisie-mode-option {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8125rem;
    color: #334155;
    cursor: pointer;
}

.saisie-mode-option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.saisie-success {
    margin: 0;
    padding: 0.65rem 0.85rem;
    border-radius: 0.25rem;
    background: #ecfdf5;
    color: #166534;
    font-size: 0.8125rem;
}

.saisie-error {
    margin: 0;
    padding: 0.65rem 0.85rem;
    border-radius: 0.25rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.8125rem;
}

.saisie-empty {
    margin: 0.35rem 0 0;
    font-size: 0.75rem;
    color: #94a3b8;
}

.saisie-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}
</style>
