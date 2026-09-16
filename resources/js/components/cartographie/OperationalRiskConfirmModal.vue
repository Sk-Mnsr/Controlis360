<template>
    <Teleport to="body">
        <div v-if="open" class="risk-confirm-backdrop" @click.self="cancel">
            <div class="risk-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="risk-confirm-title">
                <header class="risk-confirm-header">
                    <h2 id="risk-confirm-title" class="risk-confirm-title">{{ title }}</h2>
                    <button type="button" class="risk-confirm-close" aria-label="Fermer" :disabled="busy" @click="cancel">×</button>
                </header>

                <div class="risk-confirm-body">
                    <p class="risk-confirm-message">{{ message }}</p>
                    <p v-if="detail" class="risk-confirm-detail">{{ detail }}</p>
                    <p v-if="error" class="risk-confirm-error">{{ error }}</p>

                    <footer class="risk-confirm-footer">
                        <button
                            type="button"
                            class="risk-form-btn risk-form-btn-secondary"
                            :disabled="busy"
                            @click="cancel"
                        >
                            Annuler
                        </button>
                        <button
                            type="button"
                            class="risk-form-btn"
                            :class="danger ? 'risk-form-btn-danger' : 'risk-form-btn-primary'"
                            :disabled="busy"
                            @click="confirm"
                        >
                            {{ busy ? 'Traitement...' : confirmLabel }}
                        </button>
                    </footer>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmation' },
    message: { type: String, default: '' },
    detail: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmer' },
    danger: { type: Boolean, default: false },
    busy: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:open', 'confirm', 'cancel']);

function cancel() {
    emit('update:open', false);
    emit('cancel');
}

function confirm() {
    emit('confirm');
}
</script>

<style scoped>
@import './risk-form-table.css';

.risk-confirm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.45);
}

.risk-confirm-modal {
    width: min(26rem, 100%);
    border-radius: 0.75rem;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
    overflow: hidden;
}

.risk-confirm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.risk-confirm-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.risk-confirm-close {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}

.risk-confirm-close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.risk-confirm-body {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    padding: 1.15rem 1.25rem 1.25rem;
}

.risk-confirm-message {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.45;
    color: #334155;
}

.risk-confirm-detail {
    margin: 0;
    padding: 0.65rem 0.75rem;
    border-radius: 0.35rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.4;
}

.risk-confirm-error {
    margin: 0;
    padding: 0.55rem 0.7rem;
    border-radius: 0.35rem;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.8rem;
}

.risk-confirm-footer {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.25rem;
}
</style>
