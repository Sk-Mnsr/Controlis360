import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import {
    fetchClientMe,
    logoutClient,
    requestClientCode,
    verifyClientCode,
} from '../services/encheresClientAuthApi';

const TOKEN_KEY = 'encheresnClientToken';
const CLIENT_KEY = 'encheresnClient';

export const useEncheresClientAuthStore = defineStore('encheresClientAuth', () => {
    const token = ref(localStorage.getItem(TOKEN_KEY) || '');
    const client = ref(readClient());
    const open = ref(false);
    const step = ref('email');
    const email = ref('');
    const loading = ref(false);
    const error = ref('');

    const isLoggedIn = computed(() => Boolean(token.value && client.value?.email));

    function show() {
        error.value = '';
        if (!email.value) {
            step.value = 'email';
        }
        open.value = true;
    }

    function hide() {
        open.value = false;
        error.value = '';
    }

    function editEmail() {
        step.value = 'email';
        error.value = '';
    }

    async function sendCode(nextEmail) {
        loading.value = true;
        error.value = '';
        try {
            const data = await requestClientCode(nextEmail);
            email.value = data.email || nextEmail;
            step.value = 'code';
        } catch (err) {
            error.value = messageFrom(err);
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function confirmCode(code) {
        loading.value = true;
        error.value = '';
        try {
            const data = await verifyClientCode(email.value, code);
            persist(data.token, data.client);
            hide();
            step.value = 'email';
            return data;
        } catch (err) {
            error.value = messageFrom(err);
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function restore() {
        if (!token.value) {
            return;
        }
        try {
            const data = await fetchClientMe(token.value);
            client.value = data.client;
            localStorage.setItem(CLIENT_KEY, JSON.stringify(data.client));
        } catch {
            clearLocal();
        }
    }

    async function logout() {
        const current = token.value;
        clearLocal();
        if (current) {
            try {
                await logoutClient(current);
            } catch {
                // la session locale est déjà fermée
            }
        }
    }

    function persist(nextToken, nextClient) {
        token.value = nextToken;
        client.value = nextClient;
        localStorage.setItem(TOKEN_KEY, nextToken);
        localStorage.setItem(CLIENT_KEY, JSON.stringify(nextClient));
    }

    function clearLocal() {
        token.value = '';
        client.value = null;
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(CLIENT_KEY);
    }

    return {
        token,
        client,
        open,
        step,
        email,
        loading,
        error,
        isLoggedIn,
        show,
        hide,
        editEmail,
        sendCode,
        confirmCode,
        restore,
        logout,
    };
});

function readClient() {
    try {
        return JSON.parse(localStorage.getItem(CLIENT_KEY) || 'null');
    } catch {
        return null;
    }
}

function messageFrom(err) {
    return err?.response?.data?.message || 'Impossible de continuer. Réessayez.';
}
