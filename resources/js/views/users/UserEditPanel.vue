<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="font-semibold">Modifier l'utilisateur</h3>
                <p class="mt-1 text-sm text-slate-500">Mettre à jour les modules, profils et accès du compte</p>
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
                <input v-model="form.name" required autocomplete="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">E-mail</label>
                <input v-model="form.email" type="email" required autocomplete="username" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Profil plateforme</label>
                <select
                    v-model="form.platform_profile"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    :disabled="!canChangePlatformProfile"
                >
                    <option value="">Selon les modules</option>
                    <option v-if="isSuperAdmin" value="super_admin">Super administrateur</option>
                    <option value="admin">Administrateur (gestion environnements / utilisateurs)</option>
                    <option value="agent_it">Agent IT</option>
                    <option value="responsable_it">Responsable IT</option>
                    <option value="responsable_regional">Responsable Régional</option>
                </select>
            </div>
            <div v-if="isGouvernanceItProfile" class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Rôle Gouvernance IT</label>
                <input
                    type="text"
                    readonly
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold tracking-wide text-slate-700"
                    :value="gouvernanceItRoleLabel"
                />
            </div>

            <UserScopeFields
                :visible="needsEnvironment"
                v-model:environment-ids="form.environment_ids"
                v-model:entity-ids="form.entity_ids"
                :environments="selectableEnvironments"
                :entities="entities"
            />

            <UserModulesFields
                v-model="form.assignments"
                :disabled="form.platform_profile === 'super_admin' || isGouvernanceItProfile"
            />

            <div>
                <label class="mb-1 block text-sm font-medium">Nouveau mot de passe</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
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
import {
    assignmentsToPayload,
    defaultModuleAssignments,
    emptyModuleAssignment,
    GOUVERNANCE_IT_PROFILES,
    primaryProfileFromAssignments,
} from '../../config/module-access';
import { modules } from '../../config/modules';
import { useAuthStore } from '../../stores/auth';
import UserModulesFields from './UserModulesFields.vue';
import UserScopeFields from './UserScopeFields.vue';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');
const needsEnvironment = computed(() => form.platform_profile !== 'super_admin');
const gouvernanceItRoleByProfile = {
    agent_it: 'AGIT',
    responsable_it: 'RESPIT',
    responsable_regional: 'RESPREG',
};
const isGouvernanceItProfile = computed(() => GOUVERNANCE_IT_PROFILES.includes(form.platform_profile));
const gouvernanceItRoleLabel = computed(() => gouvernanceItRoleByProfile[form.platform_profile] ?? '');
const canChangePlatformProfile = computed(() => {
    if (isSuperAdmin.value) return true;
    return form.platform_profile !== 'super_admin';
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
    platform_profile: '',
    environment_ids: [],
    entity_ids: [],
    assignments: defaultModuleAssignments(),
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

function enableAllModulesAsSuperAdmin() {
    form.assignments = modules.map((module) => ({
        ...emptyModuleAssignment(module.slug),
        enabled: true,
        profile: ({
            cartographie: 'controle',
            audit: 'audit',
            conformite: 'conformite',
        })[module.slug] ?? 'metier',
    }));
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
        form.platform_profile = ['super_admin', 'admin', ...GOUVERNANCE_IT_PROFILES].includes(user.profile) ? user.profile : '';
        form.environment_ids = extractScopeIds(user, 'environments', 'environment');
        form.entity_ids = extractScopeIds(user, 'entities', 'entity');
        form.assignments = defaultModuleAssignments(user);
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

    const { modules: moduleSlugs, module_profiles: moduleProfiles } = assignmentsToPayload(form.assignments);
    const bypassModuleRequirement = ['super_admin', 'admin', ...GOUVERNANCE_IT_PROFILES].includes(form.platform_profile);

    if (!moduleSlugs.length && !bypassModuleRequirement) {
        error.value = 'Sélectionnez au moins un module avec son profil.';
        saving.value = false;
        return;
    }

    const primary = primaryProfileFromAssignments(form.assignments, form.platform_profile || null);

    try {
        const payload = {
            name: form.name,
            email: form.email,
            profile: primary.profile,
            modules: form.platform_profile === 'super_admin'
                ? modules.map((module) => module.slug)
                : moduleSlugs,
            module_profiles: form.platform_profile === 'super_admin'
                ? Object.fromEntries(modules.map((module) => [module.slug, {
                    profile: 'super_admin',
                    controle_role: null,
                    audit_role: null,
                    metier_role: null,
                }]))
                : moduleProfiles,
            environment_ids: needsEnvironment.value ? form.environment_ids : [],
            entity_ids: needsEnvironment.value ? form.entity_ids : [],
            metier_role: primary.metier_role,
            controle_role: primary.controle_role,
            audit_role: primary.audit_role,
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

watch(() => form.platform_profile, (profile) => {
    if (profile === 'super_admin') {
        form.environment_ids = [];
        form.entity_ids = [];
        enableAllModulesAsSuperAdmin();
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
