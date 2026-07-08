<template>
    <section class="operational-risk-section">
        <table class="operational-risk-table">
            <thead>
                <tr>
                    <th colspan="18" class="operational-risk-title">{{ title }}</th>
                </tr>
                <tr>
                    <th class="operational-risk-head" rowspan="2">N°</th>
                    <th class="operational-risk-head" rowspan="2">Processus</th>
                    <th class="operational-risk-head" rowspan="2">Ratio</th>
                    <th class="operational-risk-head" rowspan="2">Sous processus</th>
                    <th class="operational-risk-head" rowspan="2">Exceptions majeures</th>
                    <th class="operational-risk-head" rowspan="2">Risques corrélés</th>
                    <th class="operational-risk-head operational-risk-head-family" rowspan="2">Famille de risque</th>
                    <th class="operational-risk-head" colspan="3">Risque brut (Rb)</th>
                    <th class="operational-risk-head" colspan="4">Dispositif de prévention et de contrôle</th>
                    <th class="operational-risk-head" colspan="3">Risque résiduel (Rr)</th>
                    <th class="operational-risk-head operational-risk-head-actions" rowspan="2">Actions</th>
                </tr>
                <tr>
                    <th class="operational-risk-subhead">G</th>
                    <th class="operational-risk-subhead">P</th>
                    <th class="operational-risk-subhead">Rb</th>
                    <th class="operational-risk-subhead">Description</th>
                    <th class="operational-risk-subhead">Existant</th>
                    <th class="operational-risk-subhead">Owner</th>
                    <th class="operational-risk-subhead">Efficacité</th>
                    <th class="operational-risk-subhead">G</th>
                    <th class="operational-risk-subhead">Pr</th>
                    <th class="operational-risk-subhead">Rr</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!rows.length">
                    <td colspan="18" class="operational-risk-empty">{{ emptyMessage }}</td>
                </tr>
                <template v-for="group in groupedRows" :key="group.key">
                    <tr v-for="(row, index) in group.exceptions" :key="row.id">
                        <template v-if="index === 0">
                            <td class="operational-risk-center" :rowspan="group.exceptions.length">
                                {{ group.process_number ?? '—' }}
                            </td>
                            <td :rowspan="group.exceptions.length">{{ group.process_name || '—' }}</td>
                            <td class="operational-risk-center" :rowspan="group.exceptions.length">
                                {{ formatRatio(group.ratio) }}
                            </td>
                            <td class="operational-risk-strong" :rowspan="group.exceptions.length">
                                {{ group.sub_process_name }}
                            </td>
                        </template>
                        <td>{{ row.major_exceptions || '—' }}</td>
                        <td>{{ row.correlated_risks || '—' }}</td>
                        <td>{{ row.risk_family || '—' }}</td>
                        <td class="operational-risk-center">{{ row.gravity ?? '—' }}</td>
                        <td class="operational-risk-center">{{ row.probability ?? '—' }}</td>
                        <td class="operational-risk-score" :style="scoreStyle(row.gross_classification)">
                            {{ row.gross_risk ?? '—' }}
                        </td>
                        <td>{{ row.control_description || '—' }}</td>
                        <td class="operational-risk-center">{{ formatExists(row.control_exists) }}</td>
                        <td>{{ row.control_owner || '—' }}</td>
                        <td class="operational-risk-center">{{ row.control_effectiveness ?? '—' }}</td>
                        <td class="operational-risk-center">{{ formatRiskScore(displayResidual(row).gravity) ?? '—' }}</td>
                        <td class="operational-risk-center">{{ formatRiskScore(displayResidual(row).probability) ?? '—' }}</td>
                        <td class="operational-risk-score" :style="residualScoreStyle(row)">
                            {{ formatRiskScore(displayResidual(row).risk) ?? '—' }}
                        </td>
                        <td class="operational-risk-actions">
                            <div
                                v-if="hasAnyAction(row)"
                                class="operational-risk-menu"
                            >
                                <button
                                    type="button"
                                    class="operational-risk-menu-trigger"
                                    :aria-expanded="openMenu?.row.id === row.id"
                                    aria-haspopup="menu"
                                    title="Actions"
                                    @click.stop="toggleMenu(row, group, $event)"
                                >
                                    <span class="operational-risk-menu-dots" aria-hidden="true">⋯</span>
                                </button>
                            </div>
                            <span
                                v-else-if="permissions.can_validate || permissions.is_entity_responsable"
                                class="operational-risk-status"
                            >
                                {{ row.status_label }}
                            </span>
                            <span v-else class="operational-risk-no-actions">—</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <Teleport to="body">
            <div
                v-if="openMenu"
                ref="menuPanelRef"
                class="operational-risk-menu-panel"
                role="menu"
                :style="menuPanelStyle"
                @click.stop
            >
                <RouterLink
                    v-if="canAddException"
                    :to="addExceptionLink(openMenu.group)"
                    class="operational-risk-menu-item operational-risk-menu-item-add"
                    role="menuitem"
                    @click="closeMenu"
                >
                    <span class="operational-risk-menu-icon">+</span>
                    Ajouter un risque
                </RouterLink>
                <button
                    v-if="canEdit(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item"
                    role="menuitem"
                    @click="handleEdit(openMenu.row, openMenu.group)"
                >
                    <svg class="operational-risk-menu-icon-svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                    Modifier
                </button>
                <button
                    v-if="canValidateAssign(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-add"
                    role="menuitem"
                    @click="handleValidate(openMenu.row)"
                >
                    Valider
                </button>
                <button
                    v-if="canRequestAgentRevision(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-delete"
                    role="menuitem"
                    @click="handleRequestAgentRevision(openMenu.row)"
                >
                    Renvoyer à l'agent
                </button>
                <button
                    v-if="canSubmit(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-submit"
                    role="menuitem"
                    @click="handleSubmit(openMenu.row)"
                >
                    <svg class="operational-risk-menu-icon-svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                    Envoyer au contrôle
                </button>
                <button
                    v-if="canSubmitEntity(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-submit"
                    role="menuitem"
                    @click="handleSubmitEntity(openMenu.row)"
                >
                    <svg class="operational-risk-menu-icon-svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                    Envoyer au contrôle
                </button>
                <button
                    v-if="canComplete(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-add"
                    role="menuitem"
                    @click="handleComplete(openMenu.row)"
                >
                    Valider la ligne
                </button>
                <button
                    v-if="canRequestEntityRevision(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-delete"
                    role="menuitem"
                    @click="handleRequestEntityRevision(openMenu.row)"
                >
                    Demander des modifications
                </button>
                <button
                    v-if="canDelete(openMenu.row)"
                    type="button"
                    class="operational-risk-menu-item operational-risk-menu-item-delete"
                    role="menuitem"
                    @click="handleDelete(openMenu.row)"
                >
                    <span class="operational-risk-menu-icon">×</span>
                    Supprimer
                </button>
            </div>
        </Teleport>
    </section>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { groupRowsBySubProcess } from '../../utils/operationalRiskGroups';
import { formatRiskScore, resolvedResidualFields, scoreStyle } from '../../utils/riskScore';

const props = defineProps({
    title: { type: String, required: true },
    rows: { type: Array, default: () => [] },
    departmentCode: { type: String, default: '' },
    departmentEnvironment: { type: String, default: '' },
    permissions: { type: Object, default: () => ({}) },
    emptyMessage: {
        type: String,
        default: 'Aucune ligne d\'analyse pour ce département.',
    },
});

const emit = defineEmits([
    'edit',
    'delete',
    'submit',
    'submit-entity',
    'complete',
    'request-entity-revision',
    'validate',
    'request-agent-revision',
]);

const openMenu = ref(null);
const menuPanelRef = ref(null);
const menuPanelStyle = ref({ top: '0px', left: '0px' });

const groupedRows = computed(() => groupRowsBySubProcess(props.rows));

const canAddException = computed(() => props.permissions.can_create_row && props.departmentCode);

function hasAnyAction(row) {
    return canAddException.value
        || canDelete(row)
        || canEdit(row)
        || canShowEntityActions(row)
        || canSubmit(row)
        || canSubmitEntity(row)
        || canValidateAssign(row)
        || canRequestAgentRevision(row)
        || canComplete(row)
        || canRequestEntityRevision(row);
}

async function positionMenu(trigger) {
    await nextTick();

    const panel = menuPanelRef.value;
    if (!panel || !trigger) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const panelWidth = panel.offsetWidth;
    const panelHeight = panel.offsetHeight;
    const margin = 8;

    let top = rect.bottom + 4;
    let left = rect.right - panelWidth;

    if (top + panelHeight > window.innerHeight - margin) {
        top = rect.top - panelHeight - 4;
    }

    if (top < margin) {
        top = margin;
    }

    if (left < margin) {
        left = margin;
    }

    if (left + panelWidth > window.innerWidth - margin) {
        left = window.innerWidth - panelWidth - margin;
    }

    menuPanelStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    };
}

async function toggleMenu(row, group, event) {
    if (openMenu.value?.row.id === row.id) {
        closeMenu();
        return;
    }

    openMenu.value = { row, group };
    await positionMenu(event.currentTarget);
}

function closeMenu() {
    openMenu.value = null;
}

function handleEdit(row, group) {
    closeMenu();
    emit('edit', { row, group });
}

function handleDelete(row) {
    closeMenu();
    emit('delete', row);
}

function handleSubmit(row) {
    closeMenu();
    emit('submit', row);
}

function handleSubmitEntity(row) {
    closeMenu();
    emit('submit-entity', row);
}

function handleComplete(row) {
    closeMenu();
    emit('complete', row);
}

function handleRequestEntityRevision(row) {
    closeMenu();
    emit('request-entity-revision', row);
}

function handleValidate(row) {
    closeMenu();
    emit('validate', row);
}

function handleRequestAgentRevision(row) {
    closeMenu();
    emit('request-agent-revision', row);
}

function onDocumentClick() {
    closeMenu();
}

function onViewportChange() {
    closeMenu();
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
});

function canDelete(row) {
    return props.permissions.can_create_row && row.status === 'draft';
}

function canSubmit(row) {
    return props.permissions.can_create_row
        && ['draft', 'revision_requested'].includes(row.status);
}

function canSubmitEntity(row) {
    return row.status === 'assigned'
        && (props.permissions.is_super_admin
            || (props.permissions.is_entity_responsable
                && Number(props.permissions.entity_id) === Number(row.assigned_entity_id)));
}

function canValidateAssign(row) {
    return props.permissions.can_validate && row.status === 'submitted';
}

function canRequestAgentRevision(row) {
    return props.permissions.can_validate && row.status === 'submitted';
}

function canComplete(row) {
    return props.permissions.can_complete_entity && row.status === 'entity_submitted';
}

function canRequestEntityRevision(row) {
    return props.permissions.can_complete_entity && row.status === 'entity_submitted';
}

function canEdit(row) {
    if (props.permissions.can_create_row && ['draft', 'revision_requested'].includes(row.status)) {
        return true;
    }

    if (row.status !== 'assigned') {
        return false;
    }

    if (props.permissions.is_super_admin) {
        return true;
    }

    if (!props.permissions.is_entity_responsable) {
        return false;
    }

    return Number(props.permissions.entity_id) === Number(row.assigned_entity_id);
}

function canShowEntityActions(row) {
    return props.permissions.is_entity_responsable
        && row.status === 'assigned'
        && Number(props.permissions.entity_id) === Number(row.assigned_entity_id);
}

function addExceptionLink(group) {
    const query = {
        entity: props.departmentCode,
        mode: 'existing',
        group: group.key,
    };

    if (props.departmentEnvironment) {
        query.environment = props.departmentEnvironment;
    }

    return {
        name: 'cartographie.saisie-risques',
        query,
    };
}

function formatRatio(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `${Number(value)}%`;
}

function formatExists(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return value ? 'OUI' : 'NON';
}

function displayResidual(row) {
    return resolvedResidualFields(row);
}

function residualScoreStyle(row) {
    return scoreStyle(row.residual_classification);
}
</script>

<style scoped>
.operational-risk-section {
    overflow-x: auto;
}

.operational-risk-table {
    min-width: 82rem;
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
    line-height: 1.35;
    color: #111111;
}

.operational-risk-table th,
.operational-risk-table td {
    border: 1px solid #111111;
    padding: 0.45rem 0.5rem;
    vertical-align: top;
}

.operational-risk-title {
    background: #c00000;
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
}

.operational-risk-head {
    background: #d1d5db;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    font-size: 0.68rem;
}

.operational-risk-head-family {
    background: #c00000;
    color: #ffffff;
}

.operational-risk-head-actions {
    width: 3.5rem;
    min-width: 3.5rem;
    position: sticky;
    right: 0;
    z-index: 4;
    background: #d1d5db;
}

.operational-risk-subhead {
    background: #e5e7eb;
    font-weight: 700;
    text-align: center;
    font-size: 0.68rem;
}

.operational-risk-center {
    text-align: center;
    white-space: nowrap;
}

.operational-risk-strong {
    font-weight: 600;
}

.operational-risk-score {
    text-align: center;
    font-weight: 700;
}

.operational-risk-empty {
    text-align: center;
    color: #64748b;
}

.operational-risk-actions {
    vertical-align: middle;
    padding: 0.25rem;
    position: sticky;
    right: 0;
    z-index: 3;
    background: #ffffff;
    box-shadow: -3px 0 6px rgba(15, 23, 42, 0.06);
}

.operational-risk-no-actions {
    display: block;
    text-align: center;
    color: #94a3b8;
}

.operational-risk-status {
    display: block;
    text-align: center;
    font-size: 0.65rem;
    line-height: 1.2;
    color: #64748b;
}

.operational-risk-menu {
    display: flex;
    justify-content: center;
}

.operational-risk-menu-trigger {
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

.operational-risk-menu-trigger:hover,
.operational-risk-menu-trigger[aria-expanded='true'] {
    background: #f8fafc;
    border-color: #94a3b8;
}

.operational-risk-menu-dots {
    font-size: 1.1rem;
    line-height: 1;
    letter-spacing: -0.05em;
    transform: translateY(-1px);
}
</style>

<style>
.operational-risk-menu-panel {
    position: fixed;
    z-index: 9999;
    min-width: 11rem;
    padding: 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #ffffff;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
}

.operational-risk-menu-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.45rem 0.6rem;
    border: none;
    border-radius: 0.375rem;
    background: transparent;
    color: #334155;
    font-size: 0.75rem;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}

.operational-risk-menu-item:hover {
    background: #f8fafc;
}

.operational-risk-menu-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    font-size: 0.95rem;
    font-weight: 600;
    flex-shrink: 0;
}

.operational-risk-menu-icon-svg {
    width: 0.85rem;
    height: 0.85rem;
    flex-shrink: 0;
}

.operational-risk-menu-item-add:hover {
    color: #16a34a;
}

.operational-risk-menu-item-delete:hover {
    color: #dc2626;
}

.operational-risk-menu-item-submit:hover {
    color: #c00000;
}
</style>
