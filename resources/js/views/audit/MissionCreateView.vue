<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Nouvelle mission</h2>
                <p class="mt-1 text-sm text-slate-500">Renseignez les informations de la mission</p>
            </div>
            <RouterLink
                :to="{ name: 'audit.missions' }"
                class="text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Retour aux missions
            </RouterLink>
        </div>

        <div class="mission-layout">
            <form id="mission-form" class="mission-form" @submit.prevent="submit">
                <div class="mission-grid">
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
                            <option value="" disabled>Sélectionner</option>
                            <option v-for="type in availableMissionTypes" :key="type.value" :value="type.value">
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

                    <div class="mission-field-full mission-pair">
                        <div class="mission-field">
                            <label class="mission-label">Département(s) concerné(s)</label>
                            <MultiSelectDropdown
                                v-model="form.entity_ids"
                                :options="departmentSelectOptions"
                                :placeholder="!form.environment_id ? 'Sélectionnez d\'abord un environnement' : 'Sélectionner un ou plusieurs départements'"
                                empty-text="Aucun département disponible"
                                :disabled="!form.environment_id || entitiesLoading"
                                trigger-class="mission-input"
                                @change="entitySelectionError = false"
                            />
                            <p v-if="entitySelectionError" class="mt-1 text-xs text-red-600">
                                Sélectionnez au moins un département
                            </p>
                        </div>

                        <div class="mission-field">
                            <label class="mission-label">Responsable</label>
                            <input
                                type="text"
                                class="mission-input mission-input-readonly"
                                :value="selectedResponsible"
                                readonly
                                placeholder="Sélectionnez un ou plusieurs départements"
                            />
                        </div>
                    </div>

                    <div class="mission-field-full mission-triple">
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

                    <div class="mission-field mission-field-full">
                        <label class="mission-label">Rapport associé (pièce jointe)</label>
                        <div class="attachment-list">
                            <div
                                v-for="(slot, index) in reportSlots"
                                :key="slot.key"
                                class="attachment-row"
                            >
                                <input
                                    type="file"
                                    class="mission-file-input"
                                    @change="onReportSelected(index, $event)"
                                />
                                <button
                                    v-if="reportSlots.length > 1"
                                    type="button"
                                    class="attachment-remove-btn"
                                    @click="removeReportSlot(index)"
                                >
                                    Retirer
                                </button>
                            </div>
                            <button type="button" class="attachment-add-btn" @click="addReportSlot">
                                + Ajouter une pièce jointe
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="mission-error">{{ error }}</p>
                <p v-if="warning" class="mission-warning">{{ warning }}</p>
                <p v-if="message" class="mission-success">{{ message }}</p>
            </form>

            <aside class="mission-actions">
                <button
                    type="submit"
                    form="mission-form"
                    class="mission-action-btn mission-action-primary"
                    :disabled="saving"
                >
                    {{ saving ? 'Envoi...' : 'Enregistrer' }}
                </button>
                <button
                    type="button"
                    class="mission-action-btn mission-action-secondary"
                    :disabled="saving"
                    @click="cancel"
                >
                    Annuler
                </button>
            </aside>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import MultiSelectDropdown from '../../components/MultiSelectDropdown.vue';
import { useMissionTypes } from '../../composables/useMissionTypes';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const { loadMissionTypes, getTypesForProfile, loading: missionTypesLoading } = useMissionTypes();

const environments = ref([]);
const entities = ref([]);
const entitiesLoading = ref(false);
const saving = ref(false);
const message = ref('');
const warning = ref('');
const error = ref('');
const entitySelectionError = ref(false);

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

const departmentSelectOptions = computed(() => entities.value.map((entity) => ({
    id: entity.id,
    name: entityLabel(entity),
})));

const selectedResponsible = computed(() => {
    const names = form.entity_ids
        .map((id) => entities.value.find((e) => Number(e.id) === id)?.responsible_name)
        .filter(Boolean)
        .flatMap((n) => n.split(',').map((p) => p.trim()))
        .filter(Boolean);

    return [...new Set(names)].join(', ');
});

const availableMissionTypes = computed(() => (
    getTypesForProfile(auth.user?.profile ?? '')
));

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
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadMissionTypes();
    await loadEnvironments();
});
</script>

<style scoped src="./mission-form.css"></style>

<style scoped>
.entity-responsible {
    color: #64748b;
}

.mission-file-input {
    flex: 1;
    min-width: 0;
    font-size: 0.875rem;
    color: #475569;
}

.attachment-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.attachment-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.attachment-remove-btn {
    flex-shrink: 0;
    border: none;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
}

.attachment-remove-btn:hover {
    color: #dc2626;
}

.attachment-add-btn {
    align-self: flex-start;
    border: 1px dashed #cbd5e1;
    border-radius: 0.5rem;
    background: #f8fafc;
    padding: 0.45rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #047857;
    cursor: pointer;
}

.attachment-add-btn:hover {
    background: #ecfdf5;
    border-color: #10b981;
}

.mission-action-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.mission-action-primary:hover:not(:disabled) {
    background: #065f46;
}

.mission-action-secondary:hover:not(:disabled) {
    background: #f8fafc;
}
</style>
