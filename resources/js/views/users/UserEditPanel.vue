<template>
    <section class="user-form">
        <header class="user-form-header">
            <div>
                <p class="user-form-kicker">Édition</p>
                <h3 class="user-form-title">Modifier l’utilisateur</h3>
                <p class="user-form-subtitle">Mettre à jour l’identité, le périmètre, les modules et l’accès</p>
            </div>
            <RouterLink :to="{ name: 'users.history' }" class="user-form-back">
                ← Retour à l’historique
            </RouterLink>
        </header>

        <div v-if="loading" class="user-form-loading">Chargement…</div>

        <form v-else class="user-form-body" @submit.prevent="updateUser">
            <fieldset class="user-form-section">
                <legend>
                    <span class="user-form-step">1</span>
                    Identité
                </legend>
                <div class="user-form-grid">
                    <div>
                        <label class="user-form-label">Nom</label>
                        <input v-model="form.name" required autocomplete="name" class="user-form-input" />
                    </div>
                    <div>
                        <label class="user-form-label">E-mail</label>
                        <input v-model="form.email" type="email" required autocomplete="username" class="user-form-input" />
                    </div>
                    <div class="user-form-span-2">
                        <label class="user-form-label">Profil plateforme</label>
                        <select
                            v-model="form.platform_profile"
                            class="user-form-input"
                            :disabled="!canChangePlatformProfile"
                        >
                            <option value="">Selon les modules</option>
                            <option v-if="isSuperAdmin" value="super_admin">Super administrateur</option>
                            <option value="admin">Administrateur (gestion environnements / utilisateurs)</option>
                        </select>
                        <p class="user-form-hint">
                            Réservé à l’administration globale. Agent IT, Responsable IT et Responsable régional se choisissent dans le module Gouvernance IT.
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset v-if="needsEnvironment" class="user-form-section">
                <legend>
                    <span class="user-form-step">2</span>
                    Périmètre
                </legend>
                <UserScopeFields
                    :visible="true"
                    v-model:environment-ids="form.environment_ids"
                    v-model:entity-ids="form.entity_ids"
                    :environments="selectableEnvironments"
                    :entities="entities"
                />
            </fieldset>

            <fieldset class="user-form-section">
                <legend>
                    <span class="user-form-step">{{ needsEnvironment ? 3 : 2 }}</span>
                    Modules
                </legend>
                <UserModulesFields
                    v-model="form.assignments"
                    :disabled="form.platform_profile === 'super_admin'"
                    :hide-heading="true"
                />
            </fieldset>

            <fieldset class="user-form-section">
                <legend>
                    <span class="user-form-step">{{ needsEnvironment ? 4 : 3 }}</span>
                    Accès
                </legend>
                <div class="user-form-grid">
                    <div>
                        <label class="user-form-label">Nouveau mot de passe</label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            class="user-form-input"
                            placeholder="Laisser vide pour ne pas changer"
                        />
                    </div>
                    <div class="user-form-activated">
                        <label class="user-form-check">
                            <input v-model="form.activated" type="checkbox" class="rounded border-slate-300" />
                            Compte actif
                        </label>
                    </div>
                </div>
            </fieldset>

            <footer class="user-form-footer">
                <div class="user-form-messages">
                    <p v-if="success" class="user-form-success">{{ success }}</p>
                    <p v-if="error" class="user-form-error">{{ error }}</p>
                </div>
                <div class="user-form-footer-actions">
                    <RouterLink :to="{ name: 'users.history' }" class="user-form-btn-secondary">
                        Annuler
                    </RouterLink>
                    <button type="submit" class="user-form-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement…' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </footer>
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
            'gouvernance-it': 'agent_it',
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
        form.platform_profile = ['super_admin', 'admin'].includes(user.profile) ? user.profile : '';
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
    const bypassModuleRequirement = ['super_admin', 'admin'].includes(form.platform_profile);

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

<style scoped>
.user-form {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.user-form-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
}

.user-form-kicker {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #c00000;
}

.user-form-title {
    margin: 0.2rem 0 0;
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
}

.user-form-subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
}

.user-form-back {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
}

.user-form-back:hover {
    color: #0f172a;
}

.user-form-loading {
    padding: 2.5rem 1.5rem;
    text-align: center;
    font-size: 0.875rem;
    color: #64748b;
}

.user-form-body {
    display: grid;
    gap: 1rem;
    padding: 1.25rem 1.5rem 1.5rem;
}

.user-form-section {
    margin: 0;
    padding: 1.1rem 1.15rem 1.2rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    background: #fff;
}

.user-form-section legend {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0 0.35rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}

.user-form-step {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.45rem;
    height: 1.45rem;
    border-radius: 999px;
    background: #c00000;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
}

.user-form-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.user-form-span-2 {
    grid-column: span 2;
}

.user-form-activated {
    display: flex;
    align-items: flex-end;
}

.user-form-check {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
}

.user-form-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
}

.user-form-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.65rem;
    padding: 0.6rem 0.75rem;
    font-size: 0.875rem;
    color: #0f172a;
    background: #fff;
}

.user-form-input:focus {
    outline: none;
    border-color: #c00000;
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.1);
}

.user-form-input-readonly {
    background: #f8fafc;
    font-weight: 600;
    letter-spacing: 0.04em;
}

.user-form-footer {
    position: sticky;
    bottom: 0.75rem;
    z-index: 5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    margin-top: 0.25rem;
    padding: 0.9rem 1rem;
    border: 1px solid #fecaca;
    border-radius: 0.85rem;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(8px);
}

.user-form-messages {
    min-height: 1.25rem;
}

.user-form-success {
    margin: 0;
    font-size: 0.8125rem;
    color: #047857;
}

.user-form-error {
    margin: 0;
    font-size: 0.8125rem;
    color: #b91c1c;
}

.user-form-footer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-left: auto;
}

.user-form-btn-secondary,
.user-form-btn-primary {
    display: inline-flex;
    align-items: center;
    border-radius: 0.65rem;
    padding: 0.65rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.user-form-btn-secondary {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
}

.user-form-btn-secondary:hover {
    background: #f8fafc;
}

.user-form-btn-primary {
    border: 0;
    background: #c00000;
    color: #fff;
}

.user-form-btn-primary:hover:not(:disabled) {
    background: #9f0000;
}

.user-form-btn-primary:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

@media (max-width: 768px) {
    .user-form-grid {
        grid-template-columns: 1fr;
    }

    .user-form-span-2 {
        grid-column: span 1;
    }

    .user-form-footer {
        position: static;
    }
}
</style>
