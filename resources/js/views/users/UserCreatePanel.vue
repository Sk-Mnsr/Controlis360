<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-semibold">Nouveau utilisateur</h3>
        <p class="mt-1 text-sm text-slate-500">Créer un compte et lui attribuer un profil</p>

        <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="createUser">
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
                <select v-model="form.profile" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option v-if="isSuperAdmin" value="super_admin">Super administrateur</option>
                    <option value="admin">Administrateur</option>
                    <option value="superviseur">Superviseur</option>
                    <option value="regulateur">Régulateur</option>
                    <option value="controle">Contrôle</option>
                    <option value="audit">Audit</option>
                    <option value="conformite">Conformité</option>
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
                <label class="mb-1 block text-sm font-medium">Mot de passe</label>
                <input v-model="form.password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2 flex items-center justify-between gap-4">
                <p v-if="success" class="text-sm text-emerald-700">{{ success }}</p>
                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                <button
                    type="submit"
                    class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Créer l\'utilisateur' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import UserScopeFields from './UserScopeFields.vue';

const auth = useAuthStore();
const router = useRouter();

const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');
const needsEnvironment = computed(() => form.profile !== 'super_admin');
const selectableEnvironments = computed(() => {
    if (isSuperAdmin.value) {
        return environments.value;
    }

    const allowedIds = auth.user?.environment_ids ?? [];
    return environments.value.filter((environment) => allowedIds.includes(environment.id));
});

const environments = ref([]);
const entities = ref([]);
const saving = ref(false);
const success = ref('');
const error = ref('');

const form = reactive({
    name: '',
    email: '',
    profile: 'controle',
    controle_role: 'agent_controle_interne',
    audit_role: 'agent_audit',
    metier_role: 'visiteur',
    environment_ids: [],
    entity_ids: [],
    password: 'Cofina@123',
});

async function loadEnvironments() {
    const { data } = await api.get('/environments');
    environments.value = data.data?.data ?? data.data ?? [];

    if (!isSuperAdmin.value && !form.environment_ids.length) {
        form.environment_ids = [...(auth.user?.environment_ids ?? [])];
    }
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

async function createUser() {
    saving.value = true;
    success.value = '';
    error.value = '';

    try {
        await api.post('/users', {
            name: form.name,
            email: form.email,
            profile: form.profile,
            password: form.password,
            environment_ids: needsEnvironment.value ? form.environment_ids : [],
            entity_ids: needsEnvironment.value ? form.entity_ids : [],
            metier_role: form.profile === 'metier' ? form.metier_role : null,
            controle_role: form.profile === 'controle' ? form.controle_role : null,
            audit_role: form.profile === 'audit' ? form.audit_role : null,
        });

        success.value = `Utilisateur « ${form.name} » créé avec succès.`;
        form.name = '';
        form.email = '';
        form.password = 'Cofina@123';

        setTimeout(() => router.push({ name: 'users.history' }), 1200);
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'Erreur lors de la création.';
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
});
</script>
