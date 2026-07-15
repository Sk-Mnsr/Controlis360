import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import api from '../api/client';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const workspace = ref(null);
    const token = ref(localStorage.getItem('userToken'));
    const loading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => Boolean(token.value && user.value));

    async function login(email, password) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await api.post('/auth/login', { email, password });
            const payload = data.data ?? data;

            token.value = payload.userToken;
            user.value = payload.user;
            workspace.value = payload.workspace ?? payload.user?.workspace;
            localStorage.setItem('userToken', payload.userToken);

            return workspace.value;
        } catch (err) {
            error.value = err.response?.data?.message
                ?? err.response?.data?.errors?.password?.[0]
                ?? 'Identifiants incorrects';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchUser() {
        if (!token.value) {
            return;
        }

        try {
            const { data } = await api.get('/auth/data');
            const payload = data.data ?? data;

            user.value = payload.user ?? payload;
            workspace.value = payload.workspace ?? payload.user?.workspace;
        } catch {
            logout();
        }
    }

    async function logout() {
        try {
            if (token.value) {
                await api.delete('/auth/logout');
            }
        } catch {
            // ignore logout errors
        } finally {
            token.value = null;
            user.value = null;
            workspace.value = null;
            localStorage.removeItem('userToken');
        }
    }

    return {
        user,
        workspace,
        token,
        loading,
        error,
        isAuthenticated,
        login,
        fetchUser,
        logout,
    };
});
