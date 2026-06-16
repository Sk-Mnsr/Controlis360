<template>
    <div class="echelle-controle-page">
        <div class="echelle-controle-actions">
            <RouterLink :to="{ name: 'cartographie.home' }" class="echelle-controle-back">
                ← Dashboard
            </RouterLink>

            <button
                v-if="isSuperAdmin && !editing"
                type="button"
                class="echelle-controle-edit-btn"
                @click="startEdit"
            >
                Modifier
            </button>
        </div>

        <div v-if="loading" class="echelle-controle-loading">Chargement...</div>

        <template v-else>
            <header class="echelle-controle-title">
                Echelle de maturité des dispositifs de contrôle interne
            </header>

            <div v-if="!editing">
                <ControlScaleTable :rows="control" />
            </div>

            <form v-else class="echelle-controle-form" @submit.prevent="save">
                <ControlScaleTableEditor
                    :rows="form.control"
                    @add-row="addRow"
                    @remove-row="removeRow"
                />

                <p v-if="error" class="echelle-controle-error">{{ error }}</p>

                <div class="echelle-controle-form-actions">
                    <button type="button" class="echelle-controle-btn-secondary" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit" class="echelle-controle-btn-primary" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import ControlScaleTable from '../../components/cartographie/ControlScaleTable.vue';
import ControlScaleTableEditor from '../../components/cartographie/ControlScaleTableEditor.vue';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const error = ref('');
const control = ref([]);

const form = reactive({
    control: [],
});

function extractPayload(data) {
    const root = data?.data ?? data;
    return root?.control ?? [];
}

async function loadEchelle() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/referentials/echelle-controle');
        control.value = extractPayload(data);
    } catch {
        error.value = 'Impossible de charger l\'échelle de contrôle.';
    } finally {
        loading.value = false;
    }
}

function startEdit() {
    form.control = control.value.map((row) => ({
        id: row.id,
        level: row.level,
        qualification: row.qualification ?? row.label ?? '',
        description: row.description ?? '',
        maturity_label: row.maturity_label ?? '',
    }));
    editing.value = true;
    error.value = '';
}

function cancelEdit() {
    editing.value = false;
    error.value = '';
}

function nextLevel(rows) {
    const levels = rows.map((row) => Number(row.level) || 0);
    return levels.length ? Math.max(...levels) + 1 : 1;
}

function addRow() {
    form.control.push({
        id: null,
        level: nextLevel(form.control),
        qualification: '',
        description: '',
        maturity_label: '',
    });
}

function removeRow(index) {
    if (form.control.length <= 1) return;
    form.control.splice(index, 1);
}

async function save() {
    saving.value = true;
    error.value = '';

    try {
        const { data } = await api.put('/referentials/echelle-controle', {
            control: form.control.map((row) => ({
                id: row.id,
                level: row.level,
                qualification: row.qualification,
                description: row.description,
                maturity_label: row.maturity_label,
            })),
        });

        control.value = extractPayload(data);
        editing.value = false;
    } catch (err) {
        const errors = err.response?.data?.errors ?? err.response?.data?.data;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}

onMounted(loadEchelle);
</script>

<style scoped>
.echelle-controle-page {
    max-width: 72rem;
    margin: 0 auto;
}

.echelle-controle-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.echelle-controle-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.echelle-controle-back:hover {
    color: #0f172a;
}

.echelle-controle-edit-btn,
.echelle-controle-btn-primary {
    border: none;
    border-radius: 0.5rem;
    background: #c00000;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.echelle-controle-edit-btn:hover,
.echelle-controle-btn-primary:hover {
    opacity: 0.92;
}

.echelle-controle-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.echelle-controle-loading {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.echelle-controle-title {
    margin-bottom: 1.5rem;
    background: #1e3a8a;
    padding: 0.85rem 1rem;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #ffffff;
}

.echelle-controle-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.echelle-controle-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.echelle-controle-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.echelle-controle-btn-secondary {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}
</style>
