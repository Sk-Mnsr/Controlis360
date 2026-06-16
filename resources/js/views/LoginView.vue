<template>
    <div class="login-page">
        <section class="login-hero" :style="heroStyle">
            <div class="login-hero-overlay">
                <div class="login-hero-brand">
                    <div class="login-hero-logo-wrap">
                        <img :src="logoUrl" alt="COFINA" class="login-hero-logo" />
                    </div>
                    <h2 class="login-hero-title">Controlis360</h2>
                    <p class="login-hero-tagline">Cartographie des risques opérationnels</p>
                </div>

                <ul class="login-hero-features">
                    <li>Évaluation et pilotage des risques</li>
                    <li>Méthodologie intégrée et référentiels</li>
                    <li>Vision consolidée par environnement</li>
                </ul>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-panel-inner">
                <div class="login-card">
                    <div class="login-card-accent" aria-hidden="true" />

                    <div class="login-card-header">
                        <img :src="logoUrl" alt="COFINA" class="login-card-logo" />
                        <p class="login-card-badge">Controlis360</p>
                        <h1 class="login-card-title">Connexion</h1>
                        <p class="login-card-subtitle">Accédez à la plateforme Controlis360</p>
                    </div>

                    <form class="login-form" @submit.prevent="submit">
                        <div class="login-field">
                            <label class="login-label" for="email">Adresse e-mail</label>
                            <div class="login-input-wrap">
                                <svg class="login-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                                <input
                                    id="email"
                                    v-model="email"
                                    type="email"
                                    required
                                    autocomplete="username"
                                    class="login-input"
                                    placeholder="prenom.nom@cofinacorp.com"
                                />
                            </div>
                        </div>

                        <div class="login-field">
                            <label class="login-label" for="password">Mot de passe</label>
                            <div class="login-input-wrap">
                                <svg class="login-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <input
                                    id="password"
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    class="login-input login-input-password"
                                    placeholder="Saisissez votre mot de passe"
                                />
                                <button
                                    type="button"
                                    class="login-toggle-password"
                                    :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <p v-if="auth.error" class="login-error" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="login-error-icon" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                            </svg>
                            {{ auth.error }}
                        </p>

                        <button type="submit" class="login-submit" :disabled="auth.loading">
                            <span v-if="auth.loading" class="login-spinner" aria-hidden="true" />
                            {{ auth.loading ? 'Connexion en cours...' : 'Se connecter' }}
                        </button>
                    </form>
                </div>

                <p class="login-footer">© COFINA — Compagnie Financière Africaine</p>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const logoUrl = '/logo_Cofina.png';
const riskImageUrl = '/risk.png';

const email = ref('');
const password = ref('');
const showPassword = ref(false);

const heroStyle = computed(() => ({
    backgroundImage: `linear-gradient(120deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 41, 59, 0.78) 50%, rgba(192, 0, 0, 0.42) 100%), url('${riskImageUrl}')`,
}));

async function submit() {
    try {
        await auth.login(email.value, password.value);
        router.push({ name: 'portal' });
    } catch {
        // error handled in store
    }
}
</script>

<style scoped>
.login-page {
    display: grid;
    min-height: 100vh;
    grid-template-columns: 1fr;
    background: #f1f5f9;
}

@media (min-width: 1024px) {
    .login-page {
        grid-template-columns: 1.1fr 0.9fr;
    }
}

.login-hero {
    display: none;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

@media (min-width: 1024px) {
    .login-hero {
        display: block;
    }
}

.login-hero-overlay {
    display: flex;
    height: 100%;
    flex-direction: column;
    justify-content: center;
    gap: 2.5rem;
    padding: 3.5rem 4rem;
    color: #ffffff;
}

.login-hero-brand {
    max-width: 30rem;
    padding: 2rem 2.25rem;
    border-radius: 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(15, 23, 42, 0.35);
    backdrop-filter: blur(12px);
}

.login-hero-logo-wrap {
    display: inline-flex;
    align-items: center;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.96);
    padding: 0.65rem 1rem;
}

.login-hero-logo {
    height: 2.25rem;
    width: auto;
    object-fit: contain;
}

.login-hero-title {
    margin-top: 1.5rem;
    font-size: 2.1rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.15;
}

.login-hero-tagline {
    margin-top: 0.65rem;
    font-size: 1rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.9);
}

.login-hero-features {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    max-width: 30rem;
}

.login-hero-features li {
    position: relative;
    padding: 0.65rem 0 0.65rem 1.35rem;
    font-size: 0.95rem;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.94);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.login-hero-features li:last-child {
    border-bottom: none;
}

.login-hero-features li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 1.05rem;
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 9999px;
    background: #c00000;
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.28);
}

.login-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.5rem;
    background:
        radial-gradient(circle at 20% 20%, rgba(192, 0, 0, 0.04) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(4, 120, 87, 0.05) 0%, transparent 40%),
        linear-gradient(160deg, #f8fafc 0%, #eef2f7 100%);
}

.login-panel-inner {
    width: 100%;
    max-width: 28rem;
    animation: login-fade-in 0.45s ease-out;
}

.login-card {
    position: relative;
    overflow: hidden;
    width: 100%;
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 2.25rem 2.25rem 2rem;
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.04),
        0 16px 48px rgba(15, 23, 42, 0.1);
}

@media (min-width: 640px) {
    .login-card {
        padding: 2.75rem 2.75rem 2.25rem;
    }
}

.login-card-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #c00000 0%, #e11d48 50%, #047857 100%);
}

.login-card-header {
    margin-bottom: 2rem;
    text-align: center;
}

.login-card-logo {
    height: 2.5rem;
    width: auto;
    margin: 0 auto;
    object-fit: contain;
}

@media (min-width: 1024px) {
    .login-card-logo {
        display: none;
    }
}

.login-card-badge {
    display: none;
    margin-bottom: 0.75rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #c00000;
}

@media (min-width: 1024px) {
    .login-card-badge {
        display: block;
    }
}

.login-card-title {
    margin-top: 1rem;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
}

@media (min-width: 1024px) {
    .login-card-title {
        margin-top: 0;
    }
}

.login-card-title::after {
    content: '';
    display: block;
    width: 2.5rem;
    height: 3px;
    margin: 0.65rem auto 0;
    border-radius: 9999px;
    background: #c00000;
}

.login-card-subtitle {
    margin-top: 0.85rem;
    font-size: 0.9rem;
    line-height: 1.55;
    color: #64748b;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.35rem;
}

.login-field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.login-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
}

.login-input-wrap {
    position: relative;
}

.login-input-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    width: 1.1rem;
    height: 1.1rem;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

.login-input {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.75rem 0.85rem 0.75rem 2.5rem;
    font-size: 0.9rem;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s, background-color 0.15s;
}

.login-input::placeholder {
    color: #94a3b8;
}

.login-input:focus {
    border-color: #047857;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.12);
}

.login-input-password {
    padding-right: 2.75rem;
}

.login-toggle-password {
    position: absolute;
    right: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    color: #64748b;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 0.375rem;
    transition: color 0.15s, background-color 0.15s;
}

.login-toggle-password:hover {
    color: #0f172a;
    background: #f1f5f9;
}

.login-error {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    border-radius: 0.75rem;
    border: 1px solid #fecaca;
    background: #fef2f2;
    padding: 0.75rem 0.85rem;
    font-size: 0.85rem;
    line-height: 1.45;
    color: #b91c1c;
}

.login-error-icon {
    width: 1.1rem;
    height: 1.1rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.login-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    margin-top: 0.35rem;
    border: none;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #c00000 0%, #9a0000 100%);
    padding: 0.85rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
    box-shadow: 0 4px 14px rgba(192, 0, 0, 0.28);
}

.login-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(192, 0, 0, 0.38);
}

.login-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #ffffff;
    border-radius: 9999px;
    animation: login-spin 0.7s linear infinite;
}

.login-footer {
    margin-top: 1.25rem;
    text-align: center;
    font-size: 0.75rem;
    color: #94a3b8;
}

@keyframes login-spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes login-fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
