<template>
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h2 class="text-xl font-semibold">{{ isEdit ? 'Modifier un environnement' : 'Ajouter un environnement' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Informations sur l'environnement</p>
        </div>

        <form class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
            <section>
                <h3 class="mb-4 font-medium text-slate-700">Informations globales</h3>

                <div class="space-y-4">
                    <div v-if="!isEdit">
                        <label class="mb-1 block text-sm font-medium">Environnement de base</label>
                        <select
                            v-model="form.duplicate_from_environment_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
                        >
                            <option :value="null">Aucun</option>
                            <option
                                v-for="option in baseOptions"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Optionnel — duplique les entités (départements, agences) d'un environnement existant.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Nom</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
                            @blur="suggestCodeFromName"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Code ISO pays (optionnel)</label>
                        <select
                            v-model="selectedIso"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-violet-500"
                            @change="applyIsoCode"
                        >
                            <option value="">— Saisir un code libre ou choisir un pays —</option>
                            <option
                                v-for="country in isoCountries"
                                :key="country.code"
                                :value="country.code"
                            >
                                {{ country.code }} — {{ country.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Remplit automatiquement le code avec l’ISO 3166-1 alpha-2 (ex. CI, SN, TG).
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Code *</label>
                        <input
                            v-model="form.code"
                            type="text"
                            required
                            maxlength="50"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase outline-none focus:border-violet-500"
                            placeholder="Ex. CI, SN, TG ou FINELLE"
                            @input="onCodeInput"
                        />
                        <p class="mt-1 text-xs text-slate-500">
                            Identifiant unique de l’environnement. Préférez un code ISO pour un pays ; un code libre pour une entité non géographique (ex. Finelle).
                        </p>
                    </div>
                </div>
            </section>

            <p v-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>

            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    @click="reset"
                >
                    Effacer
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800 disabled:opacity-60"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import {
    ENVIRONMENT_ISO_COUNTRIES,
    normalizeEnvironmentCode,
    suggestIsoCodeFromName,
} from '../../utils/environmentCodes';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const saving = ref(false);
const error = ref('');
const baseOptions = ref([]);
const isoCountries = ENVIRONMENT_ISO_COUNTRIES;
const selectedIso = ref('');
const codeTouched = ref(false);

const form = reactive({
    name: '',
    code: '',
    duplicate_from_environment_id: null,
});

function extractError(err) {
    const data = err.response?.data;
    if (!data) return 'Erreur lors de l\'enregistrement';

    if (typeof data.message === 'string' && data.message.trim()) return data.message;
    if (Array.isArray(data.message) && data.message[0]) return String(data.message[0]);

    const errors = data.errors ?? data.data?.errors;
    if (errors) {
        const first = Object.values(errors).flat()[0];
        if (first) return first;
    }

    return 'Erreur lors de l\'enregistrement';
}

function extractEnvironment(responseData) {
    const payload = responseData.data ?? responseData;
    return payload.environment ?? payload.Environment ?? payload;
}

function syncSelectedIsoFromCode() {
    const code = normalizeEnvironmentCode(form.code);
    selectedIso.value = isoCountries.some((country) => country.code === code) ? code : '';
}

function onCodeInput() {
    codeTouched.value = true;
    form.code = normalizeEnvironmentCode(form.code);
    syncSelectedIsoFromCode();
}

function applyIsoCode() {
    if (!selectedIso.value) return;
    form.code = selectedIso.value;
    codeTouched.value = true;
}

function suggestCodeFromName() {
    if (codeTouched.value && form.code) return;

    const suggested = suggestIsoCodeFromName(form.name);
    if (suggested) {
        form.code = suggested;
        selectedIso.value = suggested;
    }
}

async function loadOptions() {
    const { data } = await api.get('/environments/options');
    baseOptions.value = data.data ?? [];
}

async function loadEnvironment() {
    if (!isEdit.value) return;

    const { data } = await api.get(`/environments/${route.params.id}`);
    const environment = extractEnvironment(data);

    form.name = environment.name ?? '';
    form.code = normalizeEnvironmentCode(environment.code ?? '');
    codeTouched.value = true;
    syncSelectedIsoFromCode();
}

function reset() {
    form.name = '';
    form.code = '';
    form.duplicate_from_environment_id = null;
    selectedIso.value = '';
    codeTouched.value = false;
    error.value = '';
}

async function submit() {
    saving.value = true;
    error.value = '';

    const code = normalizeEnvironmentCode(form.code);
    if (!code) {
        error.value = 'Le code est obligatoire.';
        saving.value = false;
        return;
    }

    try {
        if (isEdit.value) {
            await api.put(`/environments/${route.params.id}`, {
                name: form.name,
                code,
            });
            router.push({ name: 'environments.detail', params: { id: route.params.id } });
        } else {
            const { data } = await api.post('/environments', {
                name: form.name,
                code,
                duplicate_from_environment_id: form.duplicate_from_environment_id,
            });
            const created = extractEnvironment(data);
            router.push({ name: 'environments.detail', params: { id: created.id } });
        }
    } catch (err) {
        error.value = extractError(err);
    } finally {
        saving.value = false;
    }
}

async function applyDuplicateFromQuery() {
    if (isEdit.value) return;

    const sourceId = route.query.duplicate_from;
    if (!sourceId) return;

    const id = Number(sourceId);
    if (!Number.isFinite(id) || id <= 0) return;

    form.duplicate_from_environment_id = id;

    try {
        const { data } = await api.get(`/environments/${id}`);
        const environment = extractEnvironment(data);
        if (!environment?.name) return;

        form.name = `${environment.name} (copie)`;
        form.code = '';
        codeTouched.value = false;
        selectedIso.value = '';
        suggestCodeFromName();
    } catch {
        // Le sélecteur reste prérempli même si le détail source est indisponible.
    }
}

onMounted(async () => {
    await loadOptions();
    await loadEnvironment();
    await applyDuplicateFromQuery();
});
</script>
