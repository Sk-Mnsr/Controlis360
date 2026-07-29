<template>
    <div class="change-password-page">
        <div class="change-password-card">
            <div class="change-password-accent" aria-hidden="true" />

            <header class="change-password-header">
                <img :src="logoUrl" alt="COFINA" class="change-password-logo" />
                <p class="change-password-badge">Controlis360</p>
                <h1 class="change-password-title">Changer le mot de passe</h1>
                <p class="change-password-subtitle">
                    Pour des raisons de sécurité, vous devez définir un nouveau mot de passe avant d’accéder à la plateforme.
                </p>
            </header>

            <form class="change-password-form" @submit.prevent="submit">
                <div class="change-password-field">
                    <label class="change-password-label" for="current_password">Mot de passe actuel</label>
                    <input
                        id="current_password"
                        v-model="form.current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="change-password-input"
                    />
                </div>

                <div class="change-password-field">
                    <label class="change-password-label" for="new_password">Nouveau mot de passe</label>
                    <input
                        id="new_password"
                        v-model="form.new_password"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="change-password-input"
                    />
                    <p class="change-password-hint">Au moins 8 caractères</p>
                </div>

                <div class="change-password-field">
                    <label class="change-password-label" for="new_password_confirmation">Confirmer le nouveau mot de passe</label>
                    <input
                        id="new_password_confirmation"
                        v-model="form.new_password_confirmation"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="change-password-input"
                    />
                </div>

                <p v-if="error" class="change-password-error" role="alert">{{ error }}</p>

                <button type="submit" class="change-password-submit" :disabled="saving">
                    {{ saving ? 'Enregistrement...' : 'Enregistrer et continuer' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const logoUrl = '/logo_Cofina.png';
const saving = ref(false);
const error = ref('');
const form = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

async function submit() {
    error.value = '';
    saving.value = true;

    try {
        await auth.changePassword({
            current_password: form.current_password,
            new_password: form.new_password,
            new_password_confirmation: form.new_password_confirmation,
        });
        router.push({ name: 'portal' });
    } catch (err) {
        const payload = err.response?.data ?? {};
        const errors = payload.errors ?? payload.data ?? {};
        const messages = Object.values(errors).flat().filter(Boolean);

        error.value = messages[0]
            || (typeof payload.message === 'string' ? payload.message : null)
            || 'Impossible de modifier le mot de passe.';
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.change-password-page {
    display: flex;
    min-height: 100vh;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.5rem;
    background:
        radial-gradient(circle at 20% 20%, rgba(192, 0, 0, 0.04) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(4, 120, 87, 0.05) 0%, transparent 40%),
        linear-gradient(160deg, #f8fafc 0%, #eef2f7 100%);
}

.change-password-card {
    position: relative;
    overflow: hidden;
    width: 100%;
    max-width: 28rem;
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 2.25rem;
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.04),
        0 16px 48px rgba(15, 23, 42, 0.1);
}

.change-password-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #c00000 0%, #e11d48 50%, #047857 100%);
}

.change-password-header {
    margin-bottom: 1.75rem;
    text-align: center;
}

.change-password-logo {
    height: 2.5rem;
    width: auto;
    margin: 0 auto;
    object-fit: contain;
}

.change-password-badge {
    margin-top: 0.85rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #c00000;
}

.change-password-title {
    margin-top: 0.35rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

.change-password-subtitle {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    line-height: 1.5;
    color: #64748b;
}

.change-password-form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.change-password-field {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.change-password-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
}

.change-password-input {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.7rem 0.9rem;
    font-size: 0.9375rem;
    color: #0f172a;
}

.change-password-input:focus {
    outline: none;
    border-color: #c00000;
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.12);
    background: #ffffff;
}

.change-password-hint {
    font-size: 0.75rem;
    color: #94a3b8;
}

.change-password-error {
    margin: 0;
    border-radius: 0.75rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 0.75rem 0.9rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.change-password-submit {
    margin-top: 0.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    border: none;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #c00000 0%, #9f1239 100%);
    padding: 0.8rem 1rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.change-password-submit:hover:not(:disabled) {
    filter: brightness(1.05);
}

.change-password-submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
