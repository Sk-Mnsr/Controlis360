<template>
    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Types de mission</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Ajoutez, modifiez ou désactivez les types de mission disponibles dans le module suivi-reco.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                    @click="openCreate"
                >
                    + Ajouter un type
                </button>
            </div>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Ordre</th>
                            <th class="px-4 py-3">Libellé</th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Profils</th>
                            <th class="px-4 py-3">Missions</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="type in types" :key="type.id">
                            <td class="px-4 py-3 text-slate-700">{{ type.sort_order ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: type.accent_color || '#047857' }"
                                    />
                                    <span class="font-medium text-slate-900">{{ type.label }}</span>
                                </div>
                                <p v-if="type.description" class="mt-1 text-xs text-slate-500">{{ type.description }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ type.value }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ profileLabels(type.profiles) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ type.missions_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="type.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                                >
                                    {{ type.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        @click="openEdit(type)"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:opacity-40"
                                        :disabled="(type.missions_count ?? 0) > 0"
                                        @click="confirmDelete(type)"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="modalOpen"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeModal"
        >
            <form class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-slate-900">
                    {{ editingType ? 'Modifier le type' : 'Nouveau type de mission' }}
                </h3>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Libellé</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Ex. Audit Interne"
                        />
                    </div>

                    <div v-if="!editingType">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Code</label>
                        <input
                            v-model="form.code"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono"
                            placeholder="Laisser vide pour génération automatique"
                        />
                        <p class="mt-1 text-xs text-slate-500">Lettres minuscules, chiffres et underscores uniquement.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Profils autorisés</label>
                        <div class="flex flex-wrap gap-3">
                            <label v-for="profile in profileOptions" :key="profile.value" class="inline-flex items-center gap-2 text-sm">
                                <input v-model="form.profiles" type="checkbox" :value="profile.value" />
                                {{ profile.label }}
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Description affichée dans l'historique des missions"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Couleur</label>
                            <input v-model="form.accent_color" type="color" class="h-10 w-full rounded-lg border border-slate-300 px-1 py-1" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Ordre</label>
                            <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" />
                        Type actif
                    </label>
                </div>

                <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="closeModal">
                        Annuler
                    </button>
                    <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useMissionTypes } from '../../composables/useMissionTypes';

const {
    managedTypes: types,
    manageLoading: loading,
    loadManagedMissionTypes,
    createMissionType,
    updateMissionType,
    deleteMissionType,
} = useMissionTypes();

const modalOpen = ref(false);
const editingType = ref(null);
const saving = ref(false);
const error = ref('');

const profileOptions = [
    { value: 'audit', label: 'Audit' },
    { value: 'controle', label: 'Contrôle' },
];

const form = reactive({
    name: '',
    code: '',
    profiles: ['audit'],
    description: '',
    accent_color: '#047857',
    sort_order: 0,
    is_active: true,
});

function profileLabels(profiles) {
    const labels = (profiles ?? []).map((profile) => {
        if (profile === 'audit') return 'Audit';
        if (profile === 'controle') return 'Contrôle';
        return profile;
    });

    return labels.length ? labels.join(', ') : '—';
}

function resetForm() {
    form.name = '';
    form.code = '';
    form.profiles = ['audit'];
    form.description = '';
    form.accent_color = '#047857';
    form.sort_order = (types.value?.length ?? 0) + 1;
    form.is_active = true;
}

function openCreate() {
    editingType.value = null;
    resetForm();
    error.value = '';
    modalOpen.value = true;
}

function openEdit(type) {
    editingType.value = type;
    form.name = type.label ?? '';
    form.code = type.value ?? '';
    form.profiles = [...(type.profiles ?? ['audit'])];
    form.description = type.description ?? '';
    form.accent_color = type.accent_color ?? '#047857';
    form.sort_order = type.sort_order ?? 0;
    form.is_active = type.is_active !== false;
    error.value = '';
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    editingType.value = null;
    error.value = '';
}

async function save() {
    if (!form.profiles.length) {
        error.value = 'Sélectionnez au moins un profil autorisé.';
        return;
    }

    saving.value = true;
    error.value = '';

    const payload = {
        name: form.name.trim(),
        profiles: form.profiles,
        description: form.description.trim(),
        accent_color: form.accent_color,
        sort_order: form.sort_order,
        is_active: form.is_active,
    };

    if (!editingType.value && form.code.trim()) {
        payload.code = form.code.trim();
    }

    try {
        if (editingType.value) {
            await updateMissionType(editingType.value.id, payload);
        } else {
            await createMissionType(payload);
        }

        closeModal();
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : err.response?.data?.message?.[0] ?? 'Enregistrement impossible.';
    } finally {
        saving.value = false;
    }
}

async function confirmDelete(type) {
    if ((type.missions_count ?? 0) > 0) {
        return;
    }

    if (!window.confirm(`Supprimer le type « ${type.label} » ?`)) {
        return;
    }

    try {
        await deleteMissionType(type.id);
    } catch (err) {
        window.alert(err.response?.data?.message?.[0] ?? 'Suppression impossible.');
    }
}

onMounted(loadManagedMissionTypes);
</script>
