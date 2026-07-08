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
                    <option value="superviseur">Superviseur</option>
                    <option value="regulateur">Régulateur</option>
                    <option value="controle">Contrôle</option>
                    <option value="audit">Audit</option>
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
            <div v-if="form.profile === 'audit'">
                <label class="mb-1 block text-sm font-medium">Rôle audit</label>
                <select v-model="form.audit_role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="agent_audit">Agent audit</option>
                    <option value="responsable_audit">Responsable audit</option>
                </select>
            </div>
            <div v-if="form.profile === 'metier'">
                <label class="mb-1 block text-sm font-medium">Rôle métier</label>
                <select v-model="form.metier_role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="responsable_entite">Responsable entité</option>
                    <option value="groupe">Groupe</option>
                    <option value="visiteur">Visiteur</option>
                    <option value="agent">Agent</option>
                </select>
            </div>

            <UserScopeFields
                :visible="needsEnvironment"
                v-model:environment-ids="form.environment_ids"
                v-model:entity-ids="form.entity_ids"
                :environments="selectableEnvironments"
                :entities="entities"
            />

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
import UserScopeFields from './UserScopeFields.vue';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');
const needsEnvironment = computed(() => form.profile !== 'super_admin');
const canChangeProfile = computed(() => {
    if (isSuperAdmin.value) return true;
    return form.profile !== 'super_admin';
});
const selectableEnvironments = computed(() => {
    if (isSuperAdmin.value) {
        return environments.value;
    }

    const allowedIds = auth.user?.environment_ids ?? [];
    return environments.value.filter((environment) => allowedIds.includes(environment.id));
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
    audit_role: 'agent_audit',
    metier_role: 'visiteur',
    environment_ids: [],
    entity_ids: [],
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

function extractScopeIds(user, relationKey, legacyKey) {
    const relation = user[relationKey] ?? user[relationKey.charAt(0).toUpperCase() + relationKey.slice(1)] ?? [];
    if (Array.isArray(relation) && relation.length) {
        return relation.map((item) => item.id);
    }

    const idsKey = legacyKey === 'environment' ? 'environment_ids' : 'entity_ids';
    if (Array.isArray(user[idsKey])) {
        return user[idsKey];
    }

    const legacyId = user[`${legacyKey}_id`];
    return legacyId ? [legacyId] : [];
}

async function loadEnvironments() {
    const { data } = await api.get('/environments');
    environments.value = data.data?.data ?? data.data ?? [];
}

async function loadEntities() {
    const environmentIds = isSuperAdmin.value
        ? environments.value.map((environment) => environment.id)
        : (auth.user?.environment_ids ?? []);

    if (!environmentIds.length) {
        entities.value = [];
        return;
    }

    const responses = await Promise.all(
        environmentIds.map((environmentId) => api.get(`/entities/by-environment/${environmentId}`)),
    );

    const merged = responses.flatMap(({ data: responseData }) => responseData.data ?? responseData ?? []);
    const unique = new Map(merged.map((entity) => [entity.id, entity]));
    entities.value = [...unique.values()];
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
        form.audit_role = user.audit_role ?? 'agent_audit';
        form.metier_role = user.metier_role ?? 'visiteur';
        form.environment_ids = extractScopeIds(user, 'environments', 'environment');
        form.entity_ids = extractScopeIds(user, 'entities', 'entity');
        form.activated = Boolean(user.activated);
        form.password = '';
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
            environment_ids: needsEnvironment.value ? form.environment_ids : [],
            entity_ids: needsEnvironment.value ? form.entity_ids : [],
            metier_role: form.profile === 'metier' ? form.metier_role : null,
            controle_role: form.profile === 'controle' ? form.controle_role : null,
            audit_role: form.profile === 'audit' ? form.audit_role : null,
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
        form.environment_ids = [];
        form.entity_ids = [];
    } else if (!form.environment_ids.length) {
        form.environment_ids = isSuperAdmin.value
            ? []
            : [...(auth.user?.environment_ids ?? [])];
    }
});

onMounted(async () => {
    await loadEnvironments();
    await loadEntities();
    await loadUser();
});
</script>
