<template>
    <section class="risk-workflow">
        <div v-if="allowCreate && permissions.can_create_row" class="risk-workflow-toolbar">
            <button type="button" class="risk-form-btn risk-form-btn-secondary" @click="toggleCreateSubProcess">
                {{ openCreateSubProcess ? 'Annuler' : '+ Nouveau sous-processus' }}
            </button>
        </div>

        <form v-if="allowCreate && openCreateSubProcess" class="risk-workflow-block" @submit.prevent="createSubProcess">
            <OperationalRiskSubProcessFields v-model="createSubProcessForm" :department-name="departmentName" />
            <OperationalRiskExceptionFields
                v-model="createExceptionForm"
                :risk-families="riskFamilies"
                :risk-classifications="riskClassifications"
            />
            <div class="risk-form-actions">
                <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">Enregistrer</button>
                <button type="button" class="risk-form-btn risk-form-btn-secondary" @click="openCreateSubProcess = false">Annuler</button>
            </div>
        </form>

        <div v-for="group in groupedRows" :key="group.key" class="risk-workflow-group">
            <button type="button" class="risk-workflow-group-head" @click="toggleGroup(group.key)">
                <span class="risk-workflow-chevron" :class="{ open: isGroupOpen(group.key) }">›</span>
                <span>N°{{ group.process_number ?? '—' }}</span>
                <strong>{{ group.sub_process_name }}</strong>
                <span v-if="group.process_name" class="muted">{{ group.process_name }}</span>
                <span v-if="group.ratio != null" class="muted">{{ Number(group.ratio) }}%</span>
                <span class="risk-workflow-count">{{ group.exceptions.length }} exc.</span>
            </button>

            <div v-show="isGroupOpen(group.key)" class="risk-workflow-group-content">
                <div class="risk-workflow-group-actions">
                    <button
                        v-if="allowCreate && permissions.can_create_row && canAddExceptionToGroup(group) && addExceptionGroupKey !== group.key"
                        type="button"
                        class="risk-form-btn risk-form-btn-secondary"
                        @click="startAddException(group)"
                    >
                        + Risque
                    </button>
                </div>

                <form
                    v-if="allowCreate && addExceptionGroupKey === group.key"
                    class="risk-workflow-block"
                    @submit.prevent="createException(group)"
                >
                    <OperationalRiskExceptionFields
                        v-model="addExceptionForm"
                        :risk-families="riskFamilies"
                        :risk-classifications="riskClassifications"
                    />
                    <div class="risk-form-actions">
                        <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">Enregistrer</button>
                        <button type="button" class="risk-form-btn risk-form-btn-secondary" @click="cancelAddException">Annuler</button>
                    </div>
                </form>

                <div v-for="(row, exceptionIndex) in group.exceptions" :key="row.id" class="risk-workflow-exception">
                    <button type="button" class="risk-workflow-exception-head" @click="toggleException(row.id)">
                        <span class="risk-workflow-chevron" :class="{ open: isExceptionOpen(row.id) }">›</span>
                        <span class="risk-workflow-exc-num">{{ exceptionIndex + 1 }}</span>
                        <span class="risk-workflow-exc-text">{{ row.major_exceptions || 'Sans libellé' }}</span>
                        <span v-if="row.risk_family" class="risk-workflow-exc-family">{{ row.risk_family }}</span>
                        <span
                            v-if="row.gross_risk"
                            class="risk-workflow-exc-rb"
                            :style="scoreStyle(row.gross_classification)"
                        >{{ row.gross_risk }}</span>
                    </button>

                    <div v-show="isExceptionOpen(row.id)" class="risk-workflow-exception-content">
                        <p v-if="row.revision_comment" class="risk-workflow-note">↩ {{ row.revision_comment }}</p>
                        <p v-if="row.assigned_entity" class="risk-workflow-note">
                            → {{ row.assigned_entity.name }}<span v-if="row.deadline"> · {{ formatDate(row.deadline) }}</span>
                        </p>

                        <form v-if="canEditPhase1(row)" @submit.prevent="savePhase1(row, group)">
                            <OperationalRiskExceptionFields
                                v-model="exceptionForms[row.id]"
                                :risk-families="riskFamilies"
                                :risk-classifications="riskClassifications"
                            />
                            <div class="risk-form-actions">
                                <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">Enregistrer</button>
                                <button type="button" class="risk-form-btn risk-form-btn-primary" :disabled="saving" @click="submitRow(row, group)">Soumettre</button>
                                <button
                                    v-if="row.status === 'draft'"
                                    type="button"
                                    class="risk-form-btn risk-form-btn-danger"
                                    @click="deleteRow(row)"
                                >Supprimer</button>
                            </div>
                        </form>

                        <form
                            v-else-if="permissions.can_validate && row.status === 'submitted'"
                            class="risk-workflow-validate"
                            @submit.prevent="validateRow(row)"
                        >
                            <p v-if="entityLabel(row)" class="risk-workflow-note">
                                Entité affectée : <strong>{{ entityLabel(row) }}</strong>
                            </p>
                            <div class="risk-workflow-validate-grid">
                                <label>
                                    <span>Échéance (facultatif)</span>
                                    <input v-model="validationForms[row.id].deadline" type="date" class="risk-form-input" />
                                </label>
                            </div>
                            <div class="risk-form-actions">
                                <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">Valider</button>
                                <button type="button" class="risk-form-btn risk-form-btn-secondary" @click="openRevision(row)">Modifications</button>
                            </div>
                            <div v-if="revisionOpen[row.id]" class="risk-workflow-revision">
                                <textarea v-model="revisionForms[row.id]" rows="2" required class="risk-form-textarea" placeholder="Motif..." />
                                <button type="button" class="risk-form-btn risk-form-btn-danger" :disabled="saving" @click="requestRevision(row)">Envoyer</button>
                            </div>
                        </form>

                        <form v-else-if="canEditPhase2(row)" @submit.prevent="savePhase2(row)">
                            <OperationalRiskExceptionFields
                                :model-value="exceptionForms[row.id]"
                                :risk-families="riskFamilies"
                                :risk-classifications="riskClassifications"
                                readonly
                            />
                            <Phase2Fields
                                v-model="phase2Forms[row.id]"
                                :gravity="row.gravity"
                                :probability="row.probability"
                                :risk-classifications="riskClassifications"
                            />
                            <div class="risk-form-actions">
                                <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">Finaliser</button>
                            </div>
                        </form>

                        <OperationalRiskExceptionFields
                            v-else
                            :model-value="exceptionForms[row.id]"
                            :risk-families="riskFamilies"
                            :risk-classifications="riskClassifications"
                            readonly
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import api from '../../api/client';
import OperationalRiskSubProcessFields from './OperationalRiskSubProcessFields.vue';
import OperationalRiskExceptionFields from './OperationalRiskExceptionFields.vue';
import Phase2Fields from './OperationalRiskPhase2Fields.vue';
import {
    groupRowsBySubProcess,
    subProcessFieldsFromRow,
} from '../../utils/operationalRiskGroups';
import { scoreStyle } from '../../utils/riskScore';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
    assignableEntities: { type: Array, default: () => [] },
    riskFamilies: { type: Array, default: () => [] },
    riskClassifications: { type: Array, default: () => [] },
    departmentCode: { type: String, required: true },
    departmentName: { type: String, default: '' },
    allowCreate: { type: Boolean, default: false },
});

const emit = defineEmits(['refresh']);

const saving = ref(false);
const openCreateSubProcess = ref(false);
const addExceptionGroupKey = ref(null);
const expandedGroups = reactive({});
const expandedExceptions = reactive({});
const revisionOpen = reactive({});
const createSubProcessForm = ref(emptySubProcess());
const createExceptionForm = ref(emptyException());
const addExceptionForm = ref(emptyException());
const groupSubProcessForms = reactive({});
const exceptionForms = reactive({});
const phase2Forms = reactive({});
const validationForms = reactive({});
const revisionForms = reactive({});

const groupedRows = computed(() => groupRowsBySubProcess(props.rows));

function emptySubProcess() {
    return { process_number: null, process_name: '', ratio: null, sub_process_name: '' };
}

function emptyException() {
    return { major_exceptions: '', correlated_risks: '', risk_family: '', gravity: null, probability: null };
}

function mergePayload(group, exceptionData) {
    return {
        ...groupSubProcessForms[group.key],
        ...exceptionData,
    };
}

function isGroupOpen(key) {
    return expandedGroups[key] !== false;
}

function isExceptionOpen(id) {
    return expandedExceptions[id] !== false;
}

function toggleGroup(key) {
    expandedGroups[key] = !isGroupOpen(key);
}

function toggleException(id) {
    expandedExceptions[id] = !isExceptionOpen(id);
}

function syncForms() {
    const groups = groupRowsBySubProcess(props.rows);

    groups.forEach((group) => {
        const first = group.exceptions[0];
        if (first) {
            groupSubProcessForms[group.key] = subProcessFieldsFromRow(first);
        }
    });

    props.rows.forEach((row) => {
        if (expandedExceptions[row.id] === undefined) {
            expandedExceptions[row.id] = ['draft', 'revision_requested'].includes(row.status);
        }

        exceptionForms[row.id] = {
            major_exceptions: row.major_exceptions ?? '',
            correlated_risks: row.correlated_risks ?? '',
            risk_family: row.risk_family ?? '',
            gravity: row.gravity,
            probability: row.probability,
        };
        phase2Forms[row.id] = {
            control_description: row.control_description ?? '',
            control_exists: row.control_exists,
            control_owner: row.control_owner ?? '',
            control_effectiveness: row.control_effectiveness,
            residual_gravity: row.residual_gravity,
            residual_probability: row.residual_probability,
        };
        validationForms[row.id] = {
            deadline: row.deadline ?? '',
        };
        revisionForms[row.id] = '';
        revisionOpen[row.id] = false;
    });
}

watch(() => props.rows, syncForms, { immediate: true, deep: true });

function canEditPhase1(row) {
    return props.permissions.can_create_row
        && ['draft', 'revision_requested'].includes(row.status);
}

function canEditPhase2(row) {
    if (row.status !== 'assigned') return false;
    if (props.permissions.is_super_admin) return true;
    return props.permissions.is_entity_responsable
        && Number(props.permissions.entity_id) === Number(row.assigned_entity_id);
}

function canAddExceptionToGroup(group) {
    return group.exceptions.some((row) => canEditPhase1(row));
}

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('fr-FR');
}

function entityLabel(row) {
    return row.assigned_entity?.name ?? row.entity?.name ?? '';
}

function toggleCreateSubProcess() {
    openCreateSubProcess.value = !openCreateSubProcess.value;
    if (openCreateSubProcess.value) addExceptionGroupKey.value = null;
}

function startAddException(group) {
    openCreateSubProcess.value = false;
    addExceptionGroupKey.value = group.key;
    expandedGroups[group.key] = true;
    addExceptionForm.value = emptyException();
}

function cancelAddException() {
    addExceptionGroupKey.value = null;
    addExceptionForm.value = emptyException();
}

async function createSubProcess() {
    saving.value = true;
    try {
        await api.post(`/operational-risk-rows/departments/${props.departmentCode}`, {
            ...createSubProcessForm.value,
            ...createExceptionForm.value,
        });
        createSubProcessForm.value = emptySubProcess();
        createExceptionForm.value = emptyException();
        openCreateSubProcess.value = false;
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function createException(group) {
    saving.value = true;
    try {
        await api.post(`/operational-risk-rows/departments/${props.departmentCode}`, mergePayload(group, addExceptionForm.value));
        cancelAddException();
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function savePhase1(row, group) {
    saving.value = true;
    try {
        const payload = mergePayload(group, exceptionForms[row.id]);
        await api.put(`/operational-risk-rows/${row.id}/phase1`, payload);
        await syncSubProcessToSiblings(row, group, payload);
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function syncSubProcessToSiblings(row, group, payload) {
    const subProcessPayload = {
        process_number: payload.process_number,
        process_name: payload.process_name,
        ratio: payload.ratio,
        sub_process_name: payload.sub_process_name,
    };

    for (const sibling of group.exceptions) {
        if (sibling.id === row.id) continue;
        await api.put(`/operational-risk-rows/${sibling.id}/phase1`, {
            ...exceptionForms[sibling.id],
            ...subProcessPayload,
        });
    }
}

async function submitRow(row, group) {
    saving.value = true;
    try {
        const payload = mergePayload(group, exceptionForms[row.id]);
        await api.put(`/operational-risk-rows/${row.id}/phase1`, payload);
        await syncSubProcessToSiblings(row, group, payload);
        await api.post(`/operational-risk-rows/${row.id}/submit`);
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function deleteRow(row) {
    if (!confirm('Supprimer ce risque ?')) return;
    await api.delete(`/operational-risk-rows/${row.id}`);
    emit('refresh');
}

function openRevision(row) {
    revisionOpen[row.id] = !revisionOpen[row.id];
}

async function requestRevision(row) {
    saving.value = true;
    try {
        await api.post(`/operational-risk-rows/${row.id}/request-revision`, { revision_comment: revisionForms[row.id] });
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function validateRow(row) {
    saving.value = true;
    try {
        const payload = {};
        if (validationForms[row.id].deadline) {
            payload.deadline = validationForms[row.id].deadline;
        }

        await api.post(`/operational-risk-rows/${row.id}/validate-assign`, payload);
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function savePhase2(row) {
    saving.value = true;
    try {
        await api.put(`/operational-risk-rows/${row.id}/phase2`, phase2Forms[row.id]);
        emit('refresh');
    } finally {
        saving.value = false;
    }
}

async function submitEntityPhase2(row) {
    saving.value = true;
    try {
        await api.post(`/operational-risk-rows/${row.id}/submit-entity`, phase2Forms[row.id]);
        emit('refresh');
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-workflow {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.risk-workflow-toolbar,
.risk-workflow-group-actions {
    display: flex;
    justify-content: flex-end;
}

.risk-workflow-block {
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow-x: auto;
}

.risk-workflow-group {
    border: 1px solid #111111;
    background: #ffffff;
}

.risk-workflow-group-head,
.risk-workflow-exception-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.45rem 0.65rem;
    border: none;
    background: #d1d5db;
    font-size: 0.75rem;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    color: #111111;
}

.risk-workflow-group-head:hover,
.risk-workflow-exception-head:hover {
    background: #c4c9d0;
}

.risk-workflow-group-content {
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    overflow-x: auto;
}

.risk-workflow-count {
    margin-left: auto;
    color: #64748b;
    font-size: 0.7rem;
}

.muted {
    color: #64748b;
}

.risk-workflow-chevron {
    display: inline-block;
    font-weight: 700;
    transition: transform 0.15s;
}

.risk-workflow-chevron.open {
    transform: rotate(90deg);
}

.risk-workflow-exception {
    border: 1px solid #111111;
}

.risk-workflow-exception-head {
    background: #e5e7eb;
    border-bottom: 1px solid #111111;
}

.risk-workflow-exception-content {
    padding: 0.5rem;
    overflow-x: auto;
}

.risk-workflow-exc-num {
    font-weight: 700;
    min-width: 1.25rem;
}

.risk-workflow-exc-text {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.risk-workflow-exc-family {
    font-size: 0.68rem;
    color: #c00000;
    font-weight: 600;
}

.risk-workflow-exc-rb {
    min-width: 1.75rem;
    text-align: center;
    border-radius: 0.15rem;
    padding: 0.1rem 0.35rem;
    font-weight: 700;
    font-size: 0.7rem;
}

.risk-form-readonly {
    padding: 0.45rem 0.5rem;
    font-size: 0.75rem;
}

.risk-workflow-note {
    margin: 0 0 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
}

.risk-workflow-validate-grid {
    display: grid;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

@media (min-width: 600px) {
    .risk-workflow-validate-grid {
        grid-template-columns: 1fr 10rem;
    }
}

.risk-workflow-validate-grid label span {
    display: block;
    margin-bottom: 0.2rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #475569;
}

.risk-workflow-validate-grid .risk-form-input {
    border: 1px solid #111111;
    border-radius: 0;
    padding: 0.4rem 0.5rem;
}

.risk-workflow-revision {
    margin-top: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.risk-workflow-revision .risk-form-textarea {
    border: 1px solid #111111;
}
</style>
