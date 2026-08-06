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
                        <div class="entity-menu" :class="{ open: openMenuId === entity.id }">
                            <button
                                type="button"
                                class="entity-menu-trigger"
                                :aria-expanded="openMenuId === entity.id"
                                aria-haspopup="menu"
                                aria-label="Actions"
                                @click.stop="toggleMenu(entity, $event)"
                            >
                                <span aria-hidden="true">⋮</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!entities.length">
                    <td colspan="3" class="py-6 text-center text-slate-500">Aucune entité configurée</td>
                </tr>
            </tbody>
        </table>

        <Teleport to="body">
            <div
                v-if="menuEntity"
                class="entity-menu-panel"
                role="menu"
                :style="menuStyle"
                @click.stop
            >
                <button
                    type="button"
                    class="entity-menu-item"
                    role="menuitem"
                    @click="openEditForm(menuEntity)"
                >
                    Modifier
                </button>
                <button
                    type="button"
                    class="entity-menu-item"
                    role="menuitem"
                    @click="duplicateEntity(menuEntity)"
                >
                    Dupliquer
                </button>
                <button
                    type="button"
                    class="entity-menu-item danger"
                    role="menuitem"
                    @click="removeEntity(menuEntity)"
                >
                    Supprimer
                </button>
            </div>
        </Teleport>
    </section>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
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
const openMenuId = ref(null);
const menuEntity = ref(null);
const menuStyle = ref({});
const entityForm = reactive({
    type: 'department',
    name: '',
    code: '',
});

function toggleMenu(entity, event) {
    if (openMenuId.value === entity.id) {
        closeMenu();
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const openUp = window.innerHeight - rect.bottom < 160;

    menuStyle.value = {
        top: openUp ? `${rect.top - 4}px` : `${rect.bottom + 4}px`,
        right: `${window.innerWidth - rect.right}px`,
        transform: openUp ? 'translateY(-100%)' : 'none',
    };
    openMenuId.value = entity.id;
    menuEntity.value = entity;
}

function closeMenu() {
    openMenuId.value = null;
    menuEntity.value = null;
}

function onDocumentClick() {
    closeMenu();
}

function onWindowScrollOrResize() {
    closeMenu();
}

function resetForm() {
    entityForm.type = 'department';
    entityForm.name = '';
    entityForm.code = '';
    formError.value = '';
    editingEntity.value = null;
}

function openCreateForm() {
    closeMenu();
    resetForm();
    showEntityForm.value = true;
}

function openEditForm(entity) {
    closeMenu();
    editingEntity.value = entity;
    entityForm.type = entity.type === 'agency' ? 'agency' : 'department';
    entityForm.name = entity.name ?? '';
    entityForm.code = entity.code ?? '';
    formError.value = '';
    showEntityForm.value = true;
}

function duplicateEntity(entity) {
    closeMenu();
    editingEntity.value = null;
    entityForm.type = entity.type === 'agency' ? 'agency' : 'department';
    entityForm.name = entity.name ? `${entity.name} (copie)` : '';
    entityForm.code = '';
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
    closeMenu();
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

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('scroll', onWindowScrollOrResize, true);
    window.addEventListener('resize', onWindowScrollOrResize);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('scroll', onWindowScrollOrResize, true);
    window.removeEventListener('resize', onWindowScrollOrResize);
});
</script>

<style scoped>
.entity-menu {
    position: relative;
    display: inline-flex;
    justify-content: flex-end;
}

.entity-menu-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid transparent;
    border-radius: 0.55rem;
    background: transparent;
    color: #64748b;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.entity-menu-trigger:hover,
.entity-menu.open .entity-menu-trigger {
    border-color: #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
}

.entity-menu-panel {
    position: fixed;
    z-index: 80;
    min-width: 10.5rem;
    padding: 0.35rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.entity-menu-item {
    display: flex;
    width: 100%;
    align-items: center;
    border: 0;
    border-radius: 0.45rem;
    background: transparent;
    padding: 0.55rem 0.7rem;
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 600;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}

.entity-menu-item:hover {
    background: #f8fafc;
}

.entity-menu-item.danger {
    color: #b91c1c;
}

.entity-menu-item.danger:hover {
    background: #fef2f2;
}
</style>
