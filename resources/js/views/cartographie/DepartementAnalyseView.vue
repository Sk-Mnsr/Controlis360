<template>
    <div class="departement-analyse-page">
        <div class="departement-analyse-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="departement-analyse-back">
                ← Dashboard
            </RouterLink>
            <RouterLink
                :to="{
                    name: 'cartographie.departement-historique',
                    params: { code: route.params.code },
                    query: environmentQueryParams(route),
                }"
                class="departement-analyse-historique"
            >
                Historique
            </RouterLink>
        </div>

        <div v-if="loading" class="departement-analyse-loading">Chargement...</div>

        <template v-else>
            <p v-if="error && !entity" class="departement-analyse-error">{{ error }}</p>

            <template v-else>
                <nav v-if="showValidationTabs" class="departement-analyse-tabs" aria-label="Files de validation">
                    <button
                        type="button"
                        class="departement-analyse-tab"
                        :class="{ active: activeTab === 'all' }"
                        @click="activeTab = 'all'"
                    >
                        Cartographie complète
                    </button>
                    <button
                        type="button"
                        class="departement-analyse-tab"
                        :class="{ active: activeTab === 'agent' }"
                        @click="activeTab = 'agent'"
                    >
                        Soumissions agent
                        <span v-if="agentQueueCount" class="departement-analyse-tab-badge">{{ agentQueueCount }}</span>
                    </button>
                    <button
                        type="button"
                        class="departement-analyse-tab"
                        :class="{ active: activeTab === 'entity' }"
                        @click="activeTab = 'entity'"
                    >
                        Soumissions entité
                        <span v-if="entityQueueCount" class="departement-analyse-tab-badge">{{ entityQueueCount }}</span>
                    </button>
                </nav>

                <OperationalRiskTable
                    :title="tableTitle"
                    :rows="displayedRows"
                    :department-code="route.params.code"
                    :department-environment="route.query.environment"
                    :permissions="permissions"
                    :empty-message="emptyMessage"
                    @edit="openEditModal"
                    @delete="deleteRow"
                    @submit="submitRow"
                    @submit-entity="submitEntityRow"
                    @complete="completeEntityRow"
                    @request-entity-revision="requestEntityRevisionRow"
                    @validate="openValidateModal"
                    @request-agent-revision="requestAgentRevisionRow"
                />
            </template>
        </template>

        <OperationalRiskValidateModal
            v-model:open="validateModalOpen"
            :row="validatingRow"
            @saved="loadAnalyse"
        />

        <OperationalRiskRevisionModal
            v-model:open="revisionModalOpen"
            :row="revisionRow"
            :target="revisionTarget"
            @saved="loadAnalyse"
        />

        <OperationalRiskRowEditModal
            v-model:open="editModalOpen"
            :row="editingRow"
            :group="editingGroup"
            :permissions="permissions"
            :risk-families="riskFamilies"
            :risk-classifications="riskClassifications"
            :department-name="entity?.name ?? ''"
            @saved="loadAnalyse"
            @submitted="loadAnalyse"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import { useCartographieStore } from '../../stores/cartographie';
import { environmentQueryParams } from '../../utils/entityEnvironment';
import OperationalRiskTable from '../../components/cartographie/OperationalRiskTable.vue';
import OperationalRiskRowEditModal from '../../components/cartographie/OperationalRiskRowEditModal.vue';
import OperationalRiskValidateModal from '../../components/cartographie/OperationalRiskValidateModal.vue';
import OperationalRiskRevisionModal from '../../components/cartographie/OperationalRiskRevisionModal.vue';

const route = useRoute();
const cartographie = useCartographieStore();

const loading = ref(true);
const error = ref('');
const entity = ref(null);
const title = ref('');
const rows = ref([]);
const permissions = ref({});
const assignableEntities = ref([]);
const riskFamilies = ref([]);
const riskClassifications = ref([]);
const editModalOpen = ref(false);
const validateModalOpen = ref(false);
const editingRow = ref(null);
const editingGroup = ref(null);
const validatingRow = ref(null);
const revisionModalOpen = ref(false);
const revisionRow = ref(null);
const revisionTarget = ref('agent');
const activeTab = ref('all');

const showValidationTabs = computed(() => Boolean(permissions.value.can_validate));

const agentQueueCount = computed(() =>
    rows.value.filter((row) => row.status === 'submitted').length,
);

const entityQueueCount = computed(() =>
    rows.value.filter((row) => row.status === 'entity_submitted').length,
);

const displayedRows = computed(() => {
    if (!showValidationTabs.value || activeTab.value === 'all') {
        return rows.value;
    }

    if (activeTab.value === 'agent') {
        return rows.value.filter((row) => row.status === 'submitted');
    }

    return rows.value.filter((row) => row.status === 'entity_submitted');
});

const tableTitle = computed(() => {
    if (!showValidationTabs.value || activeTab.value === 'all') {
        return title.value;
    }

    if (activeTab.value === 'agent') {
        return `${title.value} — Soumissions agent`;
    }

    return `${title.value} — Soumissions entité`;
});

const emptyMessage = computed(() => {
    if (!showValidationTabs.value || activeTab.value === 'all') {
        return 'Aucune ligne d\'analyse pour ce département.';
    }

    if (activeTab.value === 'agent') {
        return 'Aucune soumission en attente de validation par l\'agent du contrôle.';
    }

    return 'Aucune soumission en attente de validation par le responsable d\'entité.';
});

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        entity: root?.entity ?? null,
        title: root?.title ?? '',
        rows: root?.rows ?? [],
        permissions: root?.permissions ?? {},
        assignableEntities: root?.assignable_entities ?? [],
        riskFamilies: root?.risk_families ?? [],
        riskClassifications: root?.risk_classifications ?? [],
    };
}

async function loadAnalyse() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/referentials/analyse-risques/${route.params.code}`, {
            params: environmentQueryParams(route),
        });
        const payload = extractPayload(data);
        entity.value = payload.entity;
        title.value = payload.title;
        rows.value = payload.rows;
        permissions.value = payload.permissions;
        assignableEntities.value = payload.assignableEntities;
        riskFamilies.value = payload.riskFamilies;
        riskClassifications.value = payload.riskClassifications;
        cartographie.selectedEntityCode = route.params.code;
        cartographie.selectedEntityId = payload.entity?.id ?? null;
    } catch {
        error.value = 'Impossible de charger l\'analyse des risques du département.';
    } finally {
        loading.value = false;
    }
}

function openEditModal({ row, group }) {
    editingRow.value = row;
    editingGroup.value = group;
    editModalOpen.value = true;
}

async function deleteRow(row) {
    if (!confirm('Supprimer ce risque ?')) {
        return;
    }

    try {
        await api.delete(`/operational-risk-rows/${row.id}`);
        await loadAnalyse();
    } catch {
        alert('Impossible de supprimer cette ligne.');
    }
}

function openValidateModal(row) {
    validatingRow.value = row;
    validateModalOpen.value = true;
}

function openRevisionModal(row, target) {
    revisionRow.value = row;
    revisionTarget.value = target;
    revisionModalOpen.value = true;
}

function requestAgentRevisionRow(row) {
    openRevisionModal(row, 'agent');
}

function requestEntityRevisionRow(row) {
    openRevisionModal(row, 'entity');
}

async function submitRow(row) {
    if (!confirm('Envoyer cette ligne pour validation par le contrôle ?')) {
        return;
    }

    try {
        await api.post(`/operational-risk-rows/${row.id}/submit`);
        await loadAnalyse();
    } catch {
        alert('Impossible d\'envoyer cette ligne.');
    }
}

async function submitEntityRow(row) {
    if (!confirm('Envoyer cette ligne au contrôle pour validation ?')) {
        return;
    }

    try {
        await api.post(`/operational-risk-rows/${row.id}/submit-entity`, {
            control_description: row.control_description,
            control_exists: row.control_exists,
            control_owner: row.control_owner,
            control_effectiveness: row.control_effectiveness,
        });
        await loadAnalyse();
    } catch {
        alert('Impossible d\'envoyer la ligne au contrôle.');
    }
}

async function completeEntityRow(row) {
    if (!confirm('Valider définitivement cette ligne ?')) {
        return;
    }

    try {
        await api.post(`/operational-risk-rows/${row.id}/complete`);
        await loadAnalyse();
    } catch {
        alert('Impossible de valider cette ligne.');
    }
}

watch(() => [route.params.code, route.query.environment], loadAnalyse);

onMounted(loadAnalyse);
</script>

<style scoped>
.departement-analyse-page {
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.departement-analyse-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.departement-analyse-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.departement-analyse-back:hover {
    color: #0f172a;
}

.departement-analyse-historique {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    background: #fff;
    transition: background 0.15s;
}

.departement-analyse-historique:hover {
    background: #f8fafc;
}

.departement-analyse-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.departement-analyse-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.departement-analyse-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.25rem;
}

.departement-analyse-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    padding: 0.5rem 0.85rem;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.departement-analyse-tab:hover {
    color: #0f172a;
}

.departement-analyse-tab.active {
    color: #0f172a;
    border-bottom-color: #c00000;
}

.departement-analyse-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: #c00000;
    color: #ffffff;
    font-size: 0.6875rem;
    font-weight: 700;
    line-height: 1;
}
</style>
