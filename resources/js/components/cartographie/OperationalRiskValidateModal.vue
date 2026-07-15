<template>
    <Teleport to="body">
        <div v-if="open" class="risk-validate-modal-backdrop" @click.self="close">
            <div class="risk-validate-modal" role="dialog" aria-modal="true">
                <header class="risk-validate-modal-header">
                    <h2 class="risk-validate-modal-title">Valider la ligne</h2>
                    <button type="button" class="risk-validate-modal-close" aria-label="Fermer" @click="close">×</button>
                </header>

                <form v-if="row" class="risk-validate-modal-body" @submit.prevent="save">
                    <p class="risk-validate-modal-summary">
                        {{ row.sub_process_name }} — {{ row.major_exceptions || 'Sans libellé' }}
                    </p>

                    <p v-if="entityName" class="risk-validate-entity">
                        Entité affectée : <strong>{{ entityName }}</strong>
                    </p>

                    <label class="risk-validate-field">
                        <span class="risk-validate-label">Échéance (facultatif)</span>
                        <input v-model="form.deadline" type="date" class="risk-validate-input" />
                    </label>

                    <p v-if="error" class="risk-validate-error">{{ error }}</p>

                    <footer class="risk-validate-footer">
                        <button type="button" class="risk-form-btn risk-form-btn-secondary" :disabled="saving" @click="close">
                            Annuler
                        </button>
                        <button type="submit" class="risk-form-btn risk-form-btn-primary" :disabled="saving">
                            {{ saving ? 'Validation...' : 'Valider' }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '../../api/client';

const props = defineProps({
    open: { type: Boolean, default: false },
    row: { type: Object, default: null },
});

const emit = defineEmits(['update:open', 'saved']);

const saving = ref(false);
const error = ref('');
const form = ref({ deadline: '' });

const entityName = computed(() =>
    props.row?.assigned_entity?.name
    ?? props.row?.entity?.name
    ?? '',
);

function defaultDeadline() {
    const date = new Date();
    date.setDate(date.getDate() + 30);
    return date.toISOString().slice(0, 10);
}

function syncForm() {
    form.value = {
        deadline: props.row?.deadline ?? defaultDeadline(),
    };
    error.value = '';
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        syncForm();
    }
});

watch(() => props.row, () => {
    if (props.open) {
        syncForm();
    }
});

function close() {
    emit('update:open', false);
}

async function save() {
    if (!props.row) {
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        const payload = {};
        if (form.value.deadline) {
            payload.deadline = form.value.deadline;
        }

        await api.post(`/operational-risk-rows/${props.row.id}/validate-assign`, payload);
        emit('saved');
        close();
    } catch (err) {
        const messages = err.response?.data?.data ?? err.response?.data?.errors;
        if (typeof messages === 'object' && messages !== null) {
            error.value = Object.values(messages).flat().join(' ');
        } else {
            error.value = 'Impossible de valider cette ligne.';
        }
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-validate-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.45);
}

.risk-validate-modal {
    width: min(28rem, 100%);
    border-radius: 0.75rem;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
    overflow: hidden;
}

.risk-validate-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.risk-validate-modal-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.risk-validate-modal-close {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}

.risk-validate-modal-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem;
}

.risk-validate-modal-summary {
    margin: 0;
    font-size: 0.8125rem;
    color: #64748b;
}

.risk-validate-entity {
    margin: 0;
    font-size: 0.8125rem;
    color: #334155;
}

.risk-validate-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.risk-validate-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.risk-validate-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    font-family: inherit;
}

.risk-validate-error {
    margin: 0;
    padding: 0.65rem 0.85rem;
    border-radius: 0.375rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.8125rem;
}

.risk-validate-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.5rem;
}
</style>
