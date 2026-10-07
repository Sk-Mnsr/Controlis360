<template>
    <div class="set">
        <section class="set-card">
            <h3>Paramétrage front</h3>
            <p>
                Les catégories et biens gérés ici pilotent le front Client
                (mega-menu, filtres, liste d’enchères).
            </p>
            <ul>
                <li><strong>Client</strong> = vitrine publique (consultation, enchérir, compte).</li>
                <li><strong>Admin</strong> = back-office (déposer, publier, catégories, paramétrage).</li>
                <li><strong>Comité</strong> = validation / supervision (à enrichir).</li>
            </ul>
            <p class="set-note">
                Pour ajouter / modifier les <strong>sous-catégories</strong>, ouvrez le menu
                <RouterLink :to="{ name: 'vente-encheres.admin.categories' }">Catégories &amp; sous-cat.</RouterLink>
            </p>
        </section>

        <section class="set-card">
            <h3>Message au gagnant</h3>
            <p>
                Ce texte est envoyé quand le comité clique sur <strong>Message</strong> à côté du statut Gagnante.
                Variables : <code>{nom}</code>, <code>{email}</code>, <code>{lot}</code>, <code>{titre}</code>, <code>{montant}</code>.
            </p>
            <label class="set-label">
                Objet
                <input v-model="winnerSubject" type="text" class="set-input" />
            </label>
            <label class="set-label">
                Message
                <textarea v-model="winnerBody" rows="8" class="set-input" />
            </label>
            <button type="button" class="set-btn" :disabled="savingMessage" @click="saveMessage">
                {{ savingMessage ? 'Enregistrement…' : 'Enregistrer le message' }}
            </button>
            <p v-if="messageError" class="set-msg set-msg--error">{{ messageError }}</p>
            <p v-else-if="messageSaved" class="set-msg">Message enregistré.</p>
        </section>

        <section class="set-card">
            <h3>Régions affichées dans les filtres</h3>
            <textarea v-model="regionsText" rows="6" class="set-input" />
            <button type="button" class="set-btn" @click="saveRegions">Enregistrer les régions</button>
            <p v-if="message" class="set-msg">{{ message }}</p>
        </section>

        <section class="set-card set-card--danger">
            <h3>Réinitialiser les données démo</h3>
            <p>Remet catégories et enchères aux valeurs par défaut.</p>
            <button type="button" class="set-btn set-btn--danger" @click="resetAll">Réinitialiser</button>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { fetchWinnerMessage, saveWinnerMessage } from '../../../services/encheresApi';
import { useEncheresAdminStore } from '../../../stores/encheresAdmin';

const store = useEncheresAdminStore();
const regionsText = ref(store.regions.join('\n'));
const message = ref('');
const winnerSubject = ref('');
const winnerBody = ref('');
const savingMessage = ref(false);
const messageSaved = ref(false);
const messageError = ref('');

onMounted(async () => {
    try {
        const data = await fetchWinnerMessage();
        winnerSubject.value = data.subject || '';
        winnerBody.value = data.body || '';
    } catch (err) {
        messageError.value = err.response?.data?.message || 'Impossible de charger le message.';
    }
});

async function saveMessage() {
    savingMessage.value = true;
    messageSaved.value = false;
    messageError.value = '';
    try {
        const data = await saveWinnerMessage(winnerSubject.value, winnerBody.value);
        winnerSubject.value = data.subject || winnerSubject.value;
        winnerBody.value = data.body || winnerBody.value;
        messageSaved.value = true;
    } catch (err) {
        messageError.value = err.response?.data?.message || 'Enregistrement impossible.';
    } finally {
        savingMessage.value = false;
    }
}

function saveRegions() {
    store.regions = regionsText.value
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
    store.save();
    message.value = 'Régions enregistrées.';
}

function resetAll() {
    if (!window.confirm('Réinitialiser catégories et enchères ?')) return;
    store.resetToDefaults();
    regionsText.value = store.regions.join('\n');
    message.value = 'Données démo restaurées.';
}
</script>

<style scoped>
.set-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.1rem;
    margin-bottom: 1rem;
}

.set-card h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
}

.set-card p,
.set-card li {
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
}

.set-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
    margin: 0.65rem 0;
}

.set-btn {
    border-radius: 0.45rem;
    border: 0;
    background: #2563eb;
    color: #fff;
    font-weight: 700;
    font-size: 0.8125rem;
    padding: 0.55rem 0.9rem;
    cursor: pointer;
}

.set-btn--danger {
    background: #b91c1c;
}

.set-card--danger {
    border-color: #fecaca;
}

.set-label {
    display: block;
    margin-top: 0.75rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #0f172a;
}

.set-msg {
    margin: 0.65rem 0 0;
    color: #047857;
    font-size: 0.8125rem;
    font-weight: 600;
}

.set-msg--error {
    color: #b91c1c;
}

.set-note {
    margin-top: 0.85rem;
    padding: 0.65rem 0.75rem;
    background: #eff6ff;
    border-radius: 0.45rem;
    color: #1e3a8a !important;
}

.set-note a {
    color: #2563eb;
    font-weight: 700;
}
</style>
