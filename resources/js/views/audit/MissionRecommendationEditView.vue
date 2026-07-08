<template>
    <section class="flex min-h-full flex-1 flex-col bg-white">
        <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-emerald-50 to-white px-6 py-5 lg:px-8">
            <RouterLink :to="backToMissionRoute" class="text-sm font-medium text-slate-500 hover:text-slate-800">
                ← Retour à la mission
            </RouterLink>
        </div>

        <div v-if="loading" class="flex flex-1 items-center justify-center p-10 text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="loadError" class="flex flex-1 items-center justify-center p-10 text-sm text-red-600">
            {{ loadError }}
        </div>

        <form v-else class="flex flex-1 flex-col" @submit.prevent="submit">
            <div class="flex-1 overflow-y-auto px-6 py-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-emerald-800">Résumé de la mission</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs font-medium uppercase text-slate-500">Référence</dt>
                            <dd class="mt-0.5 font-semibold text-slate-900">{{ mission.reference }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-slate-500">Environnement</dt>
                            <dd class="mt-0.5 text-slate-800">{{ mission.environment_label || '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs font-medium uppercase text-slate-500">Département(s) mission</dt>
                            <dd class="mt-0.5 text-slate-800">{{ missionDepartmentsLabel }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-8">
                    <div class="border-b border-slate-200 pb-4">
                        <h1 class="text-2xl font-bold text-slate-900">Modifier la recommandation</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ mission.reference }}</p>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Référence</label>
                            <input
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800"
                                :value="recommendation.reference"
                                readonly
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Priorité</label>
                            <select v-model="form.priority" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="priority in PRIORITIES" :key="priority.value" :value="priority.value">
                                    {{ priority.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Niveau de risque</label>
                            <select v-model="form.risk_level" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="level in RISK_LEVELS" :key="level.value" :value="level.value">
                                    {{ level.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Département(s)</label>
                            <MultiSelectDropdown
                                v-model="form.entity_ids"
                                :options="departmentOptions"
                                placeholder="Sélectionner un ou plusieurs départements"
                                empty-text="Aucun département disponible pour cette mission."
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">OWNERS</label>
                            <input
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm"
                                :value="selectedResponsible"
                                readonly
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Date émission</label>
                            <input v-model="form.recommendation_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Date échéance</label>
                            <input v-model="form.due_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Thème</label>
                            <textarea v-model="form.theme" required rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Risque</label>
                            <textarea v-model="form.risk_type" required rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Recommandation</label>
                            <textarea v-model="form.recommendation_label" required rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Commentaires</label>
                            <textarea v-model="form.comments" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div v-if="existingAttachments.length" class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Pièces jointes existantes</label>
                            <ul class="space-y-1 text-sm text-slate-700">
                                <li v-for="path in existingAttachments" :key="path">{{ fileName(path) }}</li>
                            </ul>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Ajouter des pièces jointes</label>
                            <div class="space-y-2">
                                <div v-for="(slot, index) in attachmentSlots" :key="slot.key" class="flex gap-2">
                                    <input type="file" class="flex-1 text-sm" @change="onFileSelected(index, $event)" />
                                    <button
                                        v-if="attachmentSlots.length > 1"
                                        type="button"
                                        class="text-sm text-slate-500 hover:text-red-600"
                                        @click="removeSlot(index)"
                                    >
                                        Retirer
                                    </button>
                                </div>
                                <button type="button" class="text-sm font-medium text-emerald-700" @click="addSlot">
                                    + Ajouter une pièce jointe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
            </div>

            <div class="shrink-0 flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 lg:px-8">
                <RouterLink
                    :to="backToMissionRoute"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                >
                    Annuler
                </RouterLink>
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import MultiSelectDropdown from '../../components/MultiSelectDropdown.vue';
import { PRIORITIES, RISK_LEVELS } from '../../config/mission-parametrage';

const route = useRoute();
const router = useRouter();

const mission = ref(null);
const recommendation = ref(null);
const environmentDepartments = ref([]);
const loading = ref(true);
const loadError = ref('');
const saving = ref(false);
const error = ref('');
const existingAttachments = ref([]);
const attachmentSlots = ref([{ key: 1, file: null }]);
let slotKey = 1;

const form = reactive({
    entity_ids: [],
    theme: '',
    recommendation_label: '',
    risk_type: '',
    risk_level: '',
    priority: '',
    recommendation_date: '',
    due_date: '',
    comments: '',
});

const backToMissionRoute = computed(() => ({
    name: 'audit.missions.show',
    params: { id: route.params.id },
    query: {
        from: route.query.from,
        dept_id: route.query.dept_id,
    },
}));

const missionEntities = computed(() => mission.value?.entities ?? []);

const departmentOptions = computed(() => {
    const byId = new Map();

    for (const entity of environmentDepartments.value) {
        byId.set(Number(entity.id), {
            id: entity.id,
            name: entity.name,
            responsible_name: entity.responsible_name ?? '',
        });
    }

    for (const entity of missionEntities.value) {
        const id = Number(entity.id);
        const existing = byId.get(id);

        byId.set(id, {
            id,
            name: entity.name ?? existing?.name ?? '',
            responsible_name: entity.responsible_name ?? existing?.responsible_name ?? '',
        });
    }

    for (const entity of recommendation.value?.entities ?? []) {
        const id = Number(entity.id);
        const existing = byId.get(id);

        byId.set(id, {
            id,
            name: entity.name ?? existing?.name ?? '',
            responsible_name: entity.responsible_name ?? existing?.responsible_name ?? '',
        });
    }

    return [...byId.values()].sort((a, b) => String(a.name).localeCompare(String(b.name), 'fr'));
});

const missionDepartmentsLabel = computed(() => {
    const names = (mission.value?.entities ?? []).map((e) => e.name).filter(Boolean);
    return names.length ? names.join(', ') : '—';
});

const selectedResponsible = computed(() => {
    const names = form.entity_ids
        .map((id) => departmentOptions.value.find((e) => Number(e.id) === id)?.responsible_name)
        .filter(Boolean)
        .flatMap((n) => n.split(',').map((p) => p.trim()))
        .filter(Boolean);

    return [...new Set(names)].join(', ');
});

function fileName(path) {
    return String(path).split('/').pop();
}

function extractList(data) {
    if (Array.isArray(data?.data?.data)) return data.data.data;
    if (Array.isArray(data?.data)) return data.data;
    if (Array.isArray(data)) return data;
    return [];
}

async function loadDepartments(environmentId) {
    if (!environmentId) {
        environmentDepartments.value = [];
        return;
    }

    try {
        const { data } = await api.get('/referentials/entities-departments', {
            params: { environment_id: environmentId },
        });
        environmentDepartments.value = extractList(data);
    } catch {
        environmentDepartments.value = [];
    }
}

function fillForm(reco) {
    form.entity_ids = (reco.entity_ids ?? reco.entities?.map((e) => e.id) ?? []).map(Number);
    form.theme = reco.theme ?? '';
    form.recommendation_label = reco.recommendation_label ?? '';
    form.risk_type = reco.risk_type ?? '';
    form.risk_level = reco.risk_level ?? '';
    form.priority = reco.priority ?? '';
    form.recommendation_date = reco.recommendation_date ?? '';
    form.due_date = reco.due_date ?? '';
    form.comments = reco.comments ?? '';
    existingAttachments.value = reco.attachment_paths ?? [];
}

function addSlot() {
    slotKey += 1;
    attachmentSlots.value.push({ key: slotKey, file: null });
}

function removeSlot(index) {
    attachmentSlots.value.splice(index, 1);
}

function onFileSelected(index, event) {
    attachmentSlots.value[index].file = event.target.files?.[0] ?? null;
}

function buildFormData() {
    const fd = new FormData();
    form.entity_ids.forEach((id) => fd.append('entity_ids[]', String(id)));
    fd.append('theme', form.theme.trim());
    fd.append('recommendation_label', form.recommendation_label);
    fd.append('risk_type', form.risk_type.trim());
    fd.append('risk_level', form.risk_level);
    fd.append('priority', form.priority);
    fd.append('recommendation_date', form.recommendation_date);
    if (form.due_date) fd.append('due_date', form.due_date);
    if (form.comments) fd.append('comments', form.comments);
    attachmentSlots.value.map((s) => s.file).filter(Boolean).forEach((file) => fd.append('attachments[]', file));
    fd.append('_method', 'PUT');
    return fd;
}

async function loadData() {
    loading.value = true;
    loadError.value = '';

    try {
        const [missionRes, recoRes] = await Promise.all([
            api.get(`/missions/${route.params.id}`),
            api.get(`/recommendations/${route.params.recoId}`),
        ]);

        mission.value = missionRes.data?.data ?? missionRes.data;
        recommendation.value = recoRes.data?.data ?? recoRes.data;

        if (!recommendation.value?.can_edit) {
            loadError.value = 'Modification non autorisée pour cette recommandation.';
            return;
        }

        fillForm(recommendation.value);
        await loadDepartments(mission.value?.environment_id ?? recommendation.value?.environment_id);
    } catch {
        loadError.value = 'Impossible de charger la recommandation.';
    } finally {
        loading.value = false;
    }
}

async function submit() {
    if (!recommendation.value?.id) return;

    if (!form.entity_ids.length) {
        error.value = 'Veuillez sélectionner au moins un département.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await api.post(`/recommendations/${recommendation.value.id}`, buildFormData(), {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        router.push(backToMissionRoute.value);
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : err.response?.data?.message?.[0] ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

onMounted(loadData);
</script>
