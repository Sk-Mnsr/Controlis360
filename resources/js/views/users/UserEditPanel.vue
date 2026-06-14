<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="font-semibold">Modifier l'utilisateur</h3>
                <p class="mt-1 text-sm text-slate-500">Mettre à jour le profil et les accès du compte</p>
            </div>
            <RouterLink
                :to="{ name: 'users.history' }"
                class="text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Retour à l'historique
            </RouterLink>
        </div>

        <div v-if="loading" class="mt-8 text-center text-sm text-slate-500">Chargement...</div>

        <form v-else class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="updateUser">
            <div>
                <label class="mb-1 block text-sm font-medium">Nom</label>
                <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">E-mail</label>
                <input v-model="form.email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Profil</label>
                <select
                    v-model="form.profile"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    :disabled="!canChangeProfile"
                >
                    <option v-if="isSuperAdmin" value="super_admin">Super administrateur</option>
                    <option value="admin">Administrateur</option>
                    <option value="controle">Contrôle</option>
                    <option value="metier">Métier</option>
                </select>
            </div>
            <div v-if="form.profile === 'controle'">
                <label class="mb-1 block text-sm font-medium">Rôle contrôle</label>
                <select v-model="form.controle_role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="agent_controle_interne">Agent du contrôle interne</option>
                    <option value="responsable_controle_permanent">Responsable Contrôle permanent &amp; risques opérationnels</option>
                </select>
            </div>
            <div v-if="form.profile === 'metier'">
                <label class="mb-1 block text-sm font-medium">Rôle métier</label>
                <select v-model="form.metier_role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="responsable_entite">Responsable entité</option>
                    <option value="groupe">Groupe</option>
                    <option value="visiteur">Visiteur</option>
                </select>
            </div>
            <div v-if="needsEnvironment">
                <label class="mb-1 block text-sm font-medium">Environnement</label>
                <select
                    v-model="form.environment_id"
                    required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    :disabled="!isSuperAdmin"
                    @change="loadEntities"
                >
                    <option v-for="env in environments" :key="env.id" :value="env.id">{{ env.name }}</option>
                </select>
            </div>
            <div v-if="needsEnvironment">
                <label class="mb-1 block text-sm font-medium">Entité</label>
                <select v-model="form.entity_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option :value="null">Aucune</option>
                    <option v-for="entity in entities" :key="entity.id" :value="entity.id">{{ entity.name }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nouveau mot de passe</label>
                <input
                    v-model="form.password"
                    type="password"
                    minlength="8"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Laisser vide pour ne pas changer"
                />
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.activated" type="checkbox" class="rounded border-slate-300" />
                    Compte actif
                </label>
            </div>
            <div class="md:col-span-2 flex items-center justify-between gap-4">
                <p v-if="success" class="text-sm text-emerald-700">{{ success }}</p>
                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                <button
                    type="submit"
                    class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');
const needsEnvironment = computed(() => form.profile !== 'super_admin');
const canChangeProfile = computed(() => {
    if (isSuperAdmin.value) return true;
    return form.profile !== 'super_admin';
});

const loading = ref(true);
const saving = ref(false);
const success = ref('');
const error = ref('');
const environments = ref([]);
const entities = ref([]);

const form = reactive({
    name: '',
    email: '',
    profile: 'controle',
    controle_role: 'agent_controle_interne',
    metier_role: 'visiteur',
    environment_id: null,
    entity_id: null,
    password: '',
    activated: true,
});

function extractUser(payload) {
    const data = payload?.data ?? payload;
    return data?.user ?? data?.User ?? data;
}

function extractError(err) {
    const data = err.response?.data;
    if (!data) return 'Erreur lors de la mise à jour';

    const errors = data.errors ?? data.data;
    if (errors) {
        return Object.values(errors).flat().join(' ');
    }

    return data.message ?? 'Erreur lors de la mise à jour';
}

async function loadEnvironments() {
    const { data } = await api.get('/environments');
    environments.value = data.data?.data ?? data.data ?? [];
}

async function loadEntities(preserveSelection = false) {
    if (!form.environment_id) {
        entities.value = [];
        if (!preserveSelection) {
            form.entity_id = null;
        }
        return;
    }

    const previousEntityId = form.entity_id;
    const { data } = await api.get(`/entities/by-environment/${form.environment_id}`);
    entities.value = data.data ?? data ?? [];

    if (preserveSelection && previousEntityId) {
        form.entity_id = previousEntityId;
    } else if (!preserveSelection) {
        form.entity_id = null;
    }
}

async function loadUser() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/users/${route.params.id}`);
        const user = extractUser(data);

        form.name = user.name ?? '';
        form.email = user.email ?? '';
        form.profile = user.profile ?? 'metier';
        form.controle_role = user.controle_role ?? 'agent_controle_interne';
        form.metier_role = user.metier_role ?? 'visiteur';
        form.environment_id = user.environment_id ?? null;
        form.entity_id = user.entity_id ?? null;
        form.activated = Boolean(user.activated);
        form.password = '';

        await loadEntities(true);
    } catch (err) {
        error.value = extractError(err);
    } finally {
        loading.value = false;
    }
}

async function updateUser() {
    saving.value = true;
    success.value = '';
    error.value = '';

    try {
        const payload = {
            name: form.name,
            email: form.email,
            profile: form.profile,
            environment_id: needsEnvironment.value ? form.environment_id : null,
            entity_id: needsEnvironment.value ? form.entity_id : null,
            metier_role: form.profile === 'metier' ? form.metier_role : null,
            controle_role: form.profile === 'controle' ? form.controle_role : null,
            activated: form.activated,
        };

        if (form.password) {
            payload.password = form.password;
        }

        await api.put(`/users/${route.params.id}`, payload);

        success.value = 'Utilisateur mis à jour avec succès.';
        form.password = '';

        setTimeout(() => router.push({ name: 'users.history' }), 1200);
    } catch (err) {
        error.value = extractError(err);
    } finally {
        saving.value = false;
    }
}

watch(() => form.profile, (profile) => {
    if (profile === 'super_admin') {
        form.environment_id = null;
        form.entity_id = null;
    } else if (!form.environment_id) {
        if (!isSuperAdmin.value && auth.user?.environment_id) {
            form.environment_id = auth.user.environment_id;
        }
        loadEntities();
    }
});

onMounted(async () => {
    await loadEnvironments();
    await loadUser();
});
</script>
