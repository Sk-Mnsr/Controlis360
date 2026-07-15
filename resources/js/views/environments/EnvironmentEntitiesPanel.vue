<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold">Entités</h3>
                <p class="text-sm text-slate-500">Départements et agences de cet environnement</p>
            </div>
            <button
                type="button"
                class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800"
                @click="showEntityForm = true"
            >
                + Ajouter une entité
            </button>
        </div>

        <form
            v-if="showEntityForm"
            class="mb-6 grid gap-4 rounded-xl border border-violet-100 bg-violet-50 p-4 md:grid-cols-2"
            @submit.prevent="createEntity"
        >
            <div>
                <label class="mb-1 block text-sm font-medium">Type</label>
                <select v-model="entityForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="department">Département</option>
                    <option value="agency">Agence</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nom</label>
                <input v-model="entityForm.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2 flex justify-end gap-2">
                <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="showEntityForm = false">Annuler</button>
                <button type="submit" class="rounded-lg bg-violet-700 px-4 py-2 text-sm text-white">Enregistrer</button>
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
                        <button type="button" class="text-xs text-red-600 hover:underline" @click="removeEntity(entity)">
                            Supprimer
                        </button>
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
const entityForm = reactive({ type: 'department', name: '' });

async function createEntity() {
    await api.post('/entities', {
        environment_id: props.environment.id,
        type: entityForm.type,
        name: entityForm.name,
    });
    entityForm.name = '';
    showEntityForm.value = false;
    emit('refresh');
}

async function removeEntity(entity) {
    if (!confirm(`Supprimer l'entité « ${entity.name} » ?`)) return;
    await api.delete(`/entities/${entity.id}`);
    emit('refresh');
}
</script>
