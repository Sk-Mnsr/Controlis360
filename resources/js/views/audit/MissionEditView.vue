<template>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Modifier la mission</h2>
                <p class="mt-1 text-sm text-slate-500">{{ reference }}</p>
            </div>
            <RouterLink :to="{ name: 'audit.missions' }" class="text-sm font-medium text-slate-500 hover:text-slate-800">
                ← Retour aux missions
            </RouterLink>
        </div>

        <div v-if="loading" class="p-10 text-center text-sm text-slate-500">Chargement...</div>

        <div v-else-if="loadError" class="p-10 text-center text-sm text-red-600">{{ loadError }}</div>

        <div v-else class="mission-layout">
            <form id="mission-edit-form" class="mission-form" @submit.prevent="submit">
                <div class="mission-grid">
                    <div class="mission-field">
                        <label class="mission-label">Type de mission</label>
                        <select v-model="form.mission_type" required class="mission-input">
                            <option value="" disabled>Sélectionner</option>
                            <option v-for="type in availableMissionTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div class="mission-field mission-field-full">
                        <label class="mission-label">Département(s) concerné(s)</label>
                        <MultiSelectDropdown
                            v-model="form.entity_ids"
                            :options="departmentSelectOptions"
                            placeholder="Sélectionner un ou plusieurs départements"
                            empty-text="Aucun département disponible"
                            trigger-class="mission-input"
                            @change="syncResponsible"
                        />
                    </div>

                    <div class="mission-field">
                        <label class="mission-label">Auditeur</label>
                        <input v-model="form.auditor" type="text" required class="mission-input" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Date début</label>
                        <input v-model="form.start_date" type="date" required class="mission-input" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Date fin</label>
                        <input v-model="form.end_date" type="date" class="mission-input" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Date recommandation</label>
                        <input v-model="form.recommendation_date" type="date" required class="mission-input" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Niveau de risque</label>
                        <select v-model="form.risk_level" required class="mission-input">
                            <option v-for="level in RISK_LEVELS" :key="level.value" :value="level.value">
                                {{ level.label }}
                            </option>
                        </select>
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Priorité</label>
                        <select v-model="form.priority" required class="mission-input">
                            <option v-for="priority in PRIORITIES" :key="priority.value" :value="priority.value">
                                {{ priority.label }}
                            </option>
                        </select>
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Responsable</label>
                        <input v-model="form.responsible" type="text" class="mission-input mission-input-readonly" readonly />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Date échéance</label>
                        <input v-model="form.due_date" type="date" class="mission-input" />
                    </div>
                </div>

                <div class="mission-text-fields">
                    <div class="mission-field">
                        <label class="mission-label">Libellé recommandation</label>
                        <AutoResizeTextarea v-model="form.recommendation_label" :min-rows="2" required />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Détails recommandation</label>
                        <AutoResizeTextarea v-model="form.recommendation_details" :min-rows="3" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Rapport associé</label>
                        <AutoResizeTextarea v-model="form.report_reference" :min-rows="2" />
                    </div>
                    <div class="mission-field">
                        <label class="mission-label">Commentaires</label>
                        <AutoResizeTextarea v-model="form.comments" :min-rows="2" />
                    </div>
                </div>

                <p v-if="error" class="mission-error">{{ error }}</p>
                <p v-if="message" class="mission-success">{{ message }}</p>
            </form>

            <aside class="mission-actions">
                <button type="submit" form="mission-edit-form" class="mission-action-btn mission-action-primary" :disabled="saving">
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
                <button type="button" class="mission-action-btn mission-action-secondary" @click="cancel">Annuler</button>
            </aside>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import AutoResizeTextarea from '../../components/AutoResizeTextarea.vue';
import MultiSelectDropdown from '../../components/MultiSelectDropdown.vue';
import { getMissionTypesForProfile, PRIORITIES, RISK_LEVELS } from '../../config/mission-parametrage';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const loadError = ref('');
const reference = ref('');
const saving = ref(false);
const message = ref('');
const error = ref('');
const entities = ref([]);

const form = reactive({
    mission_type: '',
    entity_ids: [],
    auditor: '',
    start_date: '',
    end_date: '',
    recommendation_date: '',
    risk_level: '',
    priority: '',
    responsible: '',
    due_date: '',
    report_reference: '',
    recommendation_label: '',
    recommendation_details: '',
    comments: '',
});

const departmentSelectOptions = computed(() => entities.value.map((entity) => ({
    id: entity.id,
    name: entityLabel(entity),
})));

const availableMissionTypes = computed(() => (
    getMissionTypesForProfile(auth.user?.profile ?? '', form.mission_type || null)
));

function entityLabel(entity) {
    const env = entity.environment?.name;
    return env ? `${entity.name} (${env})` : entity.name;
}

function syncResponsible() {
    const names = form.entity_ids
        .map((id) => entities.value.find((e) => Number(e.id) === id)?.responsible_name)
        .filter(Boolean)
        .flatMap((n) => n.split(',').map((p) => p.trim()))
        .filter(Boolean);
    form.responsible = [...new Set(names)].join(', ');
}

async function loadEntities() {
    const { data } = await api.get('/referentials/entities-departments');
    entities.value = data?.data ?? data ?? [];
}

async function loadMission() {
    loading.value = true;
    loadError.value = '';

    try {
        const { data } = await api.get(`/missions/${route.params.id}`);
        const mission = data?.data ?? data;
        const rec = mission.recommendations?.[0] ?? mission.recommendation ?? {};

        reference.value = mission.reference;
        form.mission_type = mission.mission_type;
        form.entity_ids = (mission.entity_ids ?? mission.entities?.map((e) => e.id) ?? []).map(Number);
        form.auditor = mission.auditor;
        form.start_date = mission.start_date ?? '';
        form.end_date = mission.end_date ?? '';
        form.recommendation_date = rec.recommendation_date ?? '';
        form.risk_level = rec.risk_level ?? '';
        form.priority = rec.priority ?? '';
        form.responsible = rec.responsible_name ?? '';
        form.due_date = rec.due_date ?? '';
        form.report_reference = mission.report_reference ?? '';
        form.recommendation_label = rec.recommendation_label ?? '';
        form.recommendation_details = rec.recommendation_details ?? '';
        form.comments = mission.comments ?? '';
    } catch (err) {
        loadError.value = err.response?.data?.message?.[0] ?? 'Mission introuvable ou non modifiable.';
    } finally {
        loading.value = false;
    }
}

function cancel() {
    router.push({ name: 'audit.missions' });
}

async function submit() {
    saving.value = true;
    error.value = '';
    message.value = '';

    try {
        await api.put(`/missions/${route.params.id}`, {
            ...form,
            end_date: form.end_date || null,
            due_date: form.due_date || null,
            responsible: form.responsible || null,
            report_reference: form.report_reference || null,
            recommendation_details: form.recommendation_details || null,
            comments: form.comments || null,
        });
        message.value = 'Mission mise à jour.';
        setTimeout(() => router.push({ name: 'audit.missions' }), 1000);
    } catch (err) {
        error.value = err.response?.data?.message?.[0] ?? 'Erreur lors de la modification.';
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadEntities();
    await loadMission();
    syncResponsible();
});
</script>

<style scoped src="./mission-form.css"></style>
