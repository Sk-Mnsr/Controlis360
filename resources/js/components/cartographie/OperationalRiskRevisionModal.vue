<template>
    <Teleport to="body">
        <div v-if="open" class="risk-revision-modal-backdrop" @click.self="close">
            <div class="risk-revision-modal" role="dialog" aria-modal="true">
                <header class="risk-revision-modal-header">
                    <h2 class="risk-revision-modal-title">{{ config.title }}</h2>
                    <button type="button" class="risk-revision-modal-close" aria-label="Fermer" @click="close">×</button>
                </header>

                <form v-if="row" class="risk-revision-modal-body" @submit.prevent="save">
                    <p class="risk-revision-modal-summary">
                        {{ row.sub_process_name }} — {{ row.major_exceptions || 'Sans libellé' }}
                    </p>

                    <label class="risk-revision-field">
                        <span class="risk-revision-label">{{ config.label }}</span>
                        <textarea
                            v-model="comment"
                            rows="4"
                            required
                            minlength="3"
                            class="risk-revision-textarea"
                            :placeholder="config.placeholder"
                            autofocus
                        />
                        <span class="risk-revision-hint">Minimum 3 caractères</span>
                    </label>

                    <p v-if="error" class="risk-revision-error">{{ error }}</p>

                    <footer class="risk-revision-footer">
                        <button type="button" class="risk-form-btn risk-form-btn-secondary" :disabled="saving" @click="close">
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="risk-form-btn risk-form-btn-danger"
                            :disabled="saving || comment.trim().length < 3"
                        >
                            {{ saving ? 'Envoi...' : config.submitLabel }}
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
    target: {
        type: String,
        default: 'agent',
        validator: (value) => ['agent', 'entity'].includes(value),
    },
});

const emit = defineEmits(['update:open', 'saved']);

const saving = ref(false);
const error = ref('');
const comment = ref('');

const config = computed(() => {
    if (props.target === 'entity') {
        return {
            title: 'Demander des modifications',
            label: 'Motif des modifications demandées à l\'entité',
            placeholder: 'Décrivez les corrections attendues par le responsable d\'entité...',
            submitLabel: 'Demander des modifications',
            endpoint: (id) => `/operational-risk-rows/${id}/request-entity-revision`,
            fallbackError: 'Impossible de renvoyer la ligne à l\'entité.',
        };
    }

    return {
        title: 'Renvoyer à l\'agent',
        label: 'Motif du renvoi à l\'agent du contrôle',
        placeholder: 'Décrivez les corrections attendues par l\'agent du contrôle...',
        submitLabel: 'Renvoyer à l\'agent',
        endpoint: (id) => `/operational-risk-rows/${id}/request-revision`,
        fallbackError: 'Impossible de renvoyer la ligne à l\'agent.',
    };
});

function syncForm() {
    comment.value = '';
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

    const trimmed = comment.value.trim();

    if (trimmed.length < 3) {
        error.value = 'Le motif doit contenir au moins 3 caractères.';
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        await api.post(config.value.endpoint(props.row.id), {
            revision_comment: trimmed,
        });
        emit('saved');
        close();
    } catch (err) {
        const messages = err.response?.data?.data ?? err.response?.data?.errors;
        if (typeof messages === 'object' && messages !== null) {
            error.value = Object.values(messages).flat().join(' ');
        } else {
            error.value = config.value.fallbackError;
        }
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-revision-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.45);
}

.risk-revision-modal {
    width: min(32rem, 100%);
    border-radius: 0.75rem;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
    overflow: hidden;
}

.risk-revision-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.risk-revision-modal-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.risk-revision-modal-close {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}

.risk-revision-modal-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem;
}

.risk-revision-modal-summary {
    margin: 0;
    font-size: 0.8125rem;
    color: #64748b;
}

.risk-revision-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.risk-revision-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.risk-revision-textarea {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.65rem 0.75rem;
    font-size: 0.8125rem;
    font-family: inherit;
    line-height: 1.5;
    resize: vertical;
    min-height: 6rem;
}

.risk-revision-textarea:focus {
    outline: none;
    border-color: #c00000;
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.12);
}

.risk-revision-hint {
    font-size: 0.6875rem;
    color: #94a3b8;
}

.risk-revision-error {
    margin: 0;
    padding: 0.65rem 0.85rem;
    border-radius: 0.375rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.8125rem;
}

.risk-revision-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.5rem;
}

.risk-form-btn-danger {
    border: 1px solid #b91c1c;
    background: #dc2626;
    color: #ffffff;
}

.risk-form-btn-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.risk-form-btn-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
