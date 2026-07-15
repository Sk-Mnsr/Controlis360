<template>
    <Teleport to="body">
        <div v-if="open" class="risk-edit-modal-backdrop" @click.self="close">
            <div class="risk-edit-modal" role="dialog" aria-modal="true" aria-labelledby="risk-edit-modal-title">
                <header class="risk-edit-modal-header">
                    <h2 id="risk-edit-modal-title" class="risk-edit-modal-title">Modifier la ligne</h2>
                    <button type="button" class="risk-edit-modal-close" aria-label="Fermer" @click="close">×</button>
                </header>

                <form v-if="row" class="risk-edit-modal-body" @submit.prevent="save">
                    <p v-if="row.revision_comment" class="risk-edit-modal-note">↩ {{ row.revision_comment }}</p>

                    <section v-if="editPhase1" class="risk-edit-modal-section">
                        <h3 class="risk-edit-modal-section-title">Sous-processus</h3>
                        <OperationalRiskSubProcessFields
                            v-model="subProcessForm"
                            :department-name="departmentName"
                        />
                    </section>

                    <section v-if="editPhase1" class="risk-edit-modal-section">
                        <h3 class="risk-edit-modal-section-title">Risque</h3>
                        <OperationalRiskExceptionFields
                            v-model="exceptionForm"
                            :risk-families="riskFamilies"
                            :risk-classifications="riskClassifications"
                        />
                    </section>

                    <section v-else-if="editPhase2" class="risk-edit-modal-section">
                        <h3 class="risk-edit-modal-section-title">Risque (lecture seule)</h3>
                        <OperationalRiskExceptionFields
                            :model-value="exceptionForm"
                            :risk-families="riskFamilies"
                            :risk-classifications="riskClassifications"
                            readonly
                        />
                    </section>

                    <section v-if="editPhase2" class="risk-edit-modal-section">
                        <h3 class="risk-edit-modal-section-title">Dispositif de contrôle et risque résiduel</h3>
                        <Phase2Fields
                            v-model="phase2Form"
                            :gravity="row?.gravity"
                            :probability="row?.probability"
                            :risk-classifications="riskClassifications"
                        />
                    </section>

                    <p v-if="error" class="risk-edit-modal-error">{{ error }}</p>

                    <footer class="risk-edit-modal-footer">
                        <button type="button" class="risk-form-btn risk-form-btn-secondary" :disabled="saving" @click="close">
                            Annuler
                        </button>
                        <button
                            v-if="editPhase1"
                            type="button"
                            class="risk-form-btn risk-form-btn-primary"
                            :disabled="saving"
                            @click="submitRow"
                        >
                            {{ saving ? 'Envoi...' : 'Envoyer au contrôle' }}
                        </button>
                        <button
                            v-if="editPhase2"
                            type="button"
                            class="risk-form-btn risk-form-btn-primary"
                            :disabled="saving"
                            @click="submitEntityToControl"
                        >
                            {{ saving ? 'Envoi...' : 'Envoyer au contrôle' }}
                        </button>
                        <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">
                            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';
import OperationalRiskSubProcessFields from './OperationalRiskSubProcessFields.vue';
import OperationalRiskExceptionFields from './OperationalRiskExceptionFields.vue';
import Phase2Fields from './OperationalRiskPhase2Fields.vue';
import { subProcessFieldsFromRow } from '../../utils/operationalRiskGroups';
import { emptyExceptionForm } from '../../utils/operationalRiskForms';

const props = defineProps({
    open: { type: Boolean, default: false },
    row: { type: Object, default: null },
    group: { type: Object, default: null },
    permissions: { type: Object, default: () => ({}) },
    riskFamilies: { type: Array, default: () => [] },
    riskClassifications: { type: Array, default: () => [] },
    departmentName: { type: String, default: '' },
});

const emit = defineEmits(['update:open', 'saved', 'submitted']);

const saving = ref(false);
const error = ref('');
const subProcessForm = ref(emptySubProcess());
const exceptionForm = ref(emptyExceptionForm());
const phase2Form = ref(emptyPhase2());

const editPhase1 = computed(() =>
    props.row
    && props.permissions.can_create_row
    && ['draft', 'revision_requested'].includes(props.row.status),
);

const editPhase2 = computed(() => {
    if (!props.row || props.row.status !== 'assigned') {
        return false;
    }

    if (props.permissions.is_super_admin) {
        return true;
    }

    return props.permissions.is_entity_responsable
        && Number(props.permissions.entity_id) === Number(props.row.assigned_entity_id);
});

function emptySubProcess() {
    return { process_number: null, process_name: '', ratio: null, sub_process_name: '' };
}

function emptyException() {
    return emptyExceptionForm();
}

function emptyPhase2() {
    return {
        control_description: '',
        control_exists: null,
        control_owner: '',
        control_effectiveness: null,
        residual_gravity: null,
        residual_probability: null,
    };
}

function syncForms() {
    if (!props.row) {
        return;
    }

    subProcessForm.value = subProcessFieldsFromRow(props.row);
    exceptionForm.value = {
        line_date: props.row.line_date ?? '',
        major_exceptions: props.row.major_exceptions ?? '',
        correlated_risks: props.row.correlated_risks ?? '',
        risk_family: props.row.risk_family ?? '',
        gravity: props.row.gravity,
        probability: props.row.probability,
    };
    phase2Form.value = {
        control_description: props.row.control_description ?? '',
        control_exists: props.row.control_exists,
        control_owner: props.row.control_owner ?? '',
        control_effectiveness: props.row.control_effectiveness,
        residual_gravity: props.row.residual_gravity,
        residual_probability: props.row.residual_probability,
    };
    error.value = '';
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        syncForms();
    }
});

watch(() => props.row, () => {
    if (props.open) {
        syncForms();
    }
});

function close() {
    emit('update:open', false);
}

async function syncSubProcessToSiblings(payload) {
    if (!props.group) {
        return;
    }

    const subProcessPayload = {
        process_number: payload.process_number,
        process_name: payload.process_name,
        ratio: payload.ratio,
        sub_process_name: payload.sub_process_name,
    };

    for (const sibling of props.group.exceptions) {
        if (sibling.id === props.row.id) {
            continue;
        }

        await api.put(`/operational-risk-rows/${sibling.id}/phase1`, {
            line_date: sibling.line_date ?? null,
            major_exceptions: sibling.major_exceptions ?? '',
            correlated_risks: sibling.correlated_risks ?? '',
            risk_family: sibling.risk_family ?? '',
            gravity: sibling.gravity,
            probability: sibling.probability,
            ...subProcessPayload,
        });
    }
}

async function persistChanges() {
    if (editPhase1.value) {
        const payload = {
            ...subProcessForm.value,
            ...exceptionForm.value,
        };
        await api.put(`/operational-risk-rows/${props.row.id}/phase1`, payload);
        await syncSubProcessToSiblings(payload);
        return;
    }

    if (editPhase2.value) {
        await api.put(`/operational-risk-rows/${props.row.id}/phase2`, phase2Form.value);
    }
}

async function save() {
    if (!props.row) {
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await persistChanges();
        emit('saved');
        close();
    } catch (err) {
        const messages = err.response?.data?.data ?? err.response?.data?.errors;
        if (typeof messages === 'object' && messages !== null) {
            error.value = Object.values(messages).flat().join(' ');
        } else {
            error.value = 'Impossible d\'enregistrer les modifications.';
        }
    } finally {
        saving.value = false;
    }
}

async function submitRow() {
    if (!props.row || !editPhase1.value) {
        return;
    }

    if (!confirm('Envoyer cette ligne pour validation ?')) {
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await persistChanges();
        await api.post(`/operational-risk-rows/${props.row.id}/submit`);
        emit('submitted');
        close();
    } catch (err) {
        const messages = err.response?.data?.data ?? err.response?.data?.errors;
        if (typeof messages === 'object' && messages !== null) {
            error.value = Object.values(messages).flat().join(' ');
        } else {
            error.value = 'Impossible d\'envoyer la ligne.';
        }
    } finally {
        saving.value = false;
    }
}

async function submitEntityToControl() {
    if (!props.row || !editPhase2.value) {
        return;
    }

    if (!confirm('Envoyer cette ligne au contrôle pour validation ?')) {
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await api.post(`/operational-risk-rows/${props.row.id}/submit-entity`, phase2Form.value);
        emit('submitted');
        close();
    } catch (err) {
        const messages = err.response?.data?.data ?? err.response?.data?.errors;
        if (typeof messages === 'object' && messages !== null) {
            error.value = Object.values(messages).flat().join(' ');
        } else {
            error.value = 'Impossible d\'envoyer la ligne au contrôle.';
        }
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-edit-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.45);
}

.risk-edit-modal {
    width: min(56rem, 100%);
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
    border-radius: 0.75rem;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
    overflow: hidden;
}

.risk-edit-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.risk-edit-modal-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.risk-edit-modal-close {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}

.risk-edit-modal-close:hover {
    color: #0f172a;
}

.risk-edit-modal-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem;
    overflow-y: auto;
}

.risk-edit-modal-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.risk-edit-modal-section-title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #c00000;
}

.risk-edit-modal-note {
    margin: 0;
    padding: 0.65rem 0.85rem;
    border-radius: 0.375rem;
    background: #fff7ed;
    color: #9a3412;
    font-size: 0.8125rem;
}

.risk-edit-modal-error {
    margin: 0;
    padding: 0.65rem 0.85rem;
    border-radius: 0.375rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.8125rem;
}

.risk-edit-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #f1f5f9;
}
</style>
