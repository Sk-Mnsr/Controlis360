<template>
    <div v-if="auth.open" class="ench-auth-backdrop" @click.self="auth.hide()">
        <div class="ench-auth-card" role="dialog" aria-modal="true" aria-labelledby="ench-auth-title">
            <button type="button" class="ench-auth-close" aria-label="Fermer" @click="auth.hide()">×</button>

            <template v-if="auth.step === 'email'">
                <h2 id="ench-auth-title">Bienvenue chez EnchèreSN</h2>
                <p class="ench-auth-lead">
                    Saisissez votre adresse e-mail. Un code vous est envoyé pour ouvrir ou retrouver votre compte.
                </p>
                <form novalidate @submit.prevent="submitEmail">
                    <label for="ench-auth-email">Adresse e-mail</label>
                    <input
                        id="ench-auth-email"
                        v-model="email"
                        type="text"
                        inputmode="email"
                        autocomplete="email"
                        placeholder="exemple@email.com"
                        required
                    />
                    <p v-if="localError || auth.error" class="ench-auth-error">{{ localError || auth.error }}</p>
                    <button type="submit" class="ench-auth-submit" :disabled="auth.loading">
                        {{ auth.loading ? 'Envoi…' : 'Continuer' }}
                    </button>
                </form>
            </template>

            <template v-else>
                <h2 id="ench-auth-title">Vérifiez votre e-mail</h2>
                <p class="ench-auth-lead">
                    Un code à 6 chiffres a été envoyé à <strong>{{ auth.email }}</strong>. Saisissez-le pour valider votre compte.
                </p>
                <form @submit.prevent="submitCode">
                    <label for="ench-auth-code">Code reçu par e-mail</label>
                    <input
                        id="ench-auth-code"
                        v-model="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        placeholder="000000"
                        required
                    />
                    <p v-if="auth.error" class="ench-auth-error">{{ auth.error }}</p>
                    <button type="submit" class="ench-auth-submit" :disabled="auth.loading">
                        {{ auth.loading ? 'Vérification…' : 'Valider le code' }}
                    </button>
                </form>
                <div class="ench-auth-links">
                    <button type="button" :disabled="auth.loading" @click="resend">Renvoyer le code</button>
                    <button type="button" :disabled="auth.loading" @click="auth.editEmail()">Modifier l’e-mail</button>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useEncheresClientAuthStore } from '../../stores/encheresClientAuth';

const auth = useEncheresClientAuthStore();
const email = ref('');
const code = ref('');
const localError = ref('');

watch(() => auth.open, (isOpen) => {
    if (!isOpen) {
        return;
    }
    localError.value = '';
    code.value = '';
    email.value = auth.email || '';
});

function looksLikePhone(value) {
    const compact = value.replace(/[\s.\-()]/g, '');
    return /^\+?\d{8,15}$/.test(compact);
}

async function submitEmail() {
    localError.value = '';
    const value = email.value.trim();
    if (looksLikePhone(value) || !value.includes('@')) {
        localError.value = 'Saisissez une adresse e-mail. Le numéro de téléphone n’est pas accepté.';
        return;
    }
    try {
        await auth.sendCode(value);
        code.value = '';
    } catch {
        // le message est dans le store
    }
}

async function submitCode() {
    const value = code.value.replace(/\D/g, '');
    if (value.length !== 6) {
        auth.error = 'Saisissez le code à 6 chiffres reçu par e-mail.';
        return;
    }
    try {
        await auth.confirmCode(value);
    } catch {
        // le message est dans le store
    }
}

async function resend() {
    code.value = '';
    try {
        await auth.sendCode(auth.email);
    } catch {
        // le message est dans le store
    }
}
</script>

<style scoped>
.ench-auth-backdrop {
    position: fixed;
    inset: 0;
    z-index: 80;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.ench-auth-card {
    position: relative;
    width: min(100%, 28rem);
    background: #fff;
    border: 1px solid #e8eef5;
    border-radius: 1.1rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    padding: 1.75rem 1.5rem 1.4rem;
}

.ench-auth-close {
    position: absolute;
    top: 0.7rem;
    right: 0.75rem;
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 1.4rem;
    line-height: 1;
    cursor: pointer;
}

.ench-auth-card h2 {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.ench-auth-lead {
    margin: 0.75rem 0 1.15rem;
    color: #334155;
    line-height: 1.5;
    font-size: 0.95rem;
}

label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.92rem;
    color: #0f172a;
}

input {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #e2e8f0;
    border-radius: 0.8rem;
    padding: 0.85rem 0.95rem;
    font-size: 1rem;
    color: #0f172a;
    outline: none;
}

input:focus {
    border-color: #f59e0b;
}

.ench-auth-error {
    margin: 0.65rem 0 0;
    color: #b91c1c;
    font-size: 0.85rem;
}

.ench-auth-submit {
    width: 100%;
    margin-top: 1rem;
    border: 0;
    border-radius: 999px;
    background: #f59e0b;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
    padding: 0.85rem 1rem;
    cursor: pointer;
}

.ench-auth-submit:disabled {
    opacity: 0.7;
    cursor: wait;
}

.ench-auth-links {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.9rem;
}

.ench-auth-links button {
    border: 0;
    background: transparent;
    color: #2563eb;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
}
</style>
