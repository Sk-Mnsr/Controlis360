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
                        />
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

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const saving = ref(false);
const error = ref('');
const baseOptions = ref([]);

const form = reactive({
    name: '',
    duplicate_from_environment_id: null,
});

function extractError(err) {
    const data = err.response?.data;
    if (!data) return 'Erreur lors de l\'enregistrement';

    if (data.message) return data.message;

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

async function loadOptions() {
    const { data } = await api.get('/environments/options');
    baseOptions.value = data.data ?? [];
}

async function loadEnvironment() {
    if (!isEdit.value) return;

    const { data } = await api.get(`/environments/${route.params.id}`);
    const environment = extractEnvironment(data);

    form.name = environment.name;
}

function reset() {
    form.name = '';
    form.duplicate_from_environment_id = null;
    error.value = '';
}

async function submit() {
    saving.value = true;
    error.value = '';

    try {
        if (isEdit.value) {
            await api.put(`/environments/${route.params.id}`, { name: form.name });
            router.push({ name: 'environments.detail', params: { id: route.params.id } });
        } else {
            const { data } = await api.post('/environments', {
                name: form.name,
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

onMounted(async () => {
    await loadOptions();
    await loadEnvironment();
});
</script>
