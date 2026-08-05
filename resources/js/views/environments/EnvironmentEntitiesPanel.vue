<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold">Entités</h3>
                <p class="text-sm text-slate-500">Départements et agences de cet environnement</p>
            </div>
            <button
                type="button"
                class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800"
                @click="openCreateForm"
            >
                + Ajouter une entité
            </button>
        </div>

        <form
            v-if="showEntityForm"
            class="mb-6 grid gap-4 rounded-xl border border-violet-100 bg-violet-50 p-4 md:grid-cols-2"
            @submit.prevent="saveEntity"
        >
            <div class="md:col-span-2">
                <p class="text-sm font-medium text-slate-700">
                    {{ editingEntity ? 'Modifier l’entité' : 'Nouvelle entité' }}
                </p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Type</label>
                <select v-model="entityForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="department">Département</option>
                    <option value="agency">Agence</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nom</label>
                <input
                    v-model="entityForm.name"
                    required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                />
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Code</label>
                <input
                    v-model="entityForm.code"
                    maxlength="50"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase"
                    placeholder="Auto si vide"
                />
            </div>
            <p v-if="formError" class="md:col-span-2 text-sm text-red-600">{{ formError }}</p>
            <div class="md:col-span-2 flex justify-end gap-2">
                <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="closeForm">
                    Annuler
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-violet-700 px-4 py-2 text-sm text-white disabled:opacity-60"
                    :disabled="saving"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </div>
        </form>

        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-600">
                <tr>
                    <th class="py-2 font-medium">Nom</th>
                    <th class="py-2 font-medium">Type</th>
                    <th class="py-2 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="entity in entities" :key="entity.id" class="border-b border-slate-100">
                    <td class="py-3">{{ entity.name }}</td>
                    <td class="py-3">{{ entity.type_fr }}</td>
                    <td class="py-3 text-right">
                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                class="text-xs font-medium text-violet-700 hover:underline"
                                @click="openEditForm(entity)"
                            >
                                Modifier
                            </button>
                            <button
                                type="button"
                                class="text-xs text-red-600 hover:underline"
                                @click="removeEntity(entity)"
                            >
                                Supprimer
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!entities.length">
                    <td colspan="3" class="py-6 text-center text-slate-500">Aucune entité configurée</td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import api from '../../api/client';

const props = defineProps({
    environment: { type: Object, required: true },
    entities: { type: Array, default: () => [] },
});

const emit = defineEmits(['refresh']);

const showEntityForm = ref(false);
const saving = ref(false);
const formError = ref('');
const editingEntity = ref(null);
const entityForm = reactive({
    type: 'department',
    name: '',
    code: '',
});

function resetForm() {
    entityForm.type = 'department';
    entityForm.name = '';
    entityForm.code = '';
    formError.value = '';
    editingEntity.value = null;
}

function openCreateForm() {
    resetForm();
    showEntityForm.value = true;
}

function openEditForm(entity) {
    editingEntity.value = entity;
    entityForm.type = entity.type === 'agency' ? 'agency' : 'department';
    entityForm.name = entity.name ?? '';
    entityForm.code = entity.code ?? '';
    formError.value = '';
    showEntityForm.value = true;
}

function closeForm() {
    showEntityForm.value = false;
    resetForm();
}

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

async function saveEntity() {
    saving.value = true;
    formError.value = '';

    const payload = {
        type: entityForm.type,
        name: entityForm.name.trim(),
        code: entityForm.code.trim().toUpperCase() || undefined,
    };

    try {
        if (editingEntity.value) {
            await api.put(`/entities/${editingEntity.value.id}`, payload);
        } else {
            await api.post('/entities', {
                environment_id: props.environment.id,
                ...payload,
            });
        }

        closeForm();
        emit('refresh');
    } catch (err) {
        formError.value = extractError(err);
    } finally {
        saving.value = false;
    }
}

async function removeEntity(entity) {
    if (!confirm(`Supprimer l'entité « ${entity.name} » ?`)) return;

    try {
        await api.delete(`/entities/${entity.id}`);
        if (editingEntity.value?.id === entity.id) {
            closeForm();
        }
        emit('refresh');
    } catch (err) {
        alert(extractError(err));
    }
}
</script>
