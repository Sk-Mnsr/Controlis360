import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import api from '../api/client';
import { userWithModuleContext } from '../config/module-access';

const IDLE_TIMEOUT_MS = 2 * 60 * 60 * 1000; // 2 heures d'inactivité
const ACTIVITY_EVENTS = ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'];

export const useAuthStore = defineStore('auth', () => {
    const baseUser = ref(null);
    const activeModule = ref(null);
    const workspace = ref(null);
    const token = ref(localStorage.getItem('userToken'));
    const loading = ref(false);
    const error = ref(null);

    let idleTimer = null;
    let activityBound = false;

    const user = computed(() => userWithModuleContext(baseUser.value, activeModule.value));
    const isAuthenticated = computed(() => Boolean(token.value && baseUser.value));
    const mustChangePassword = computed(() => Boolean(baseUser.value?.password_change_required));

    function setBaseUser(nextUser) {
        baseUser.value = nextUser;
        workspace.value = nextUser?.workspace ?? workspace.value;
    }

    function setActiveModule(slug = null) {
        activeModule.value = slug || null;
    }

    function clearSessionLocally() {
        token.value = null;
        baseUser.value = null;
        activeModule.value = null;
        workspace.value = null;
        localStorage.removeItem('userToken');
        stopIdleWatch();
    }

    function onUserActivity() {
        if (!token.value) {
            return;
        }

        resetIdleTimer();
    }

    function resetIdleTimer() {
        if (idleTimer) {
            clearTimeout(idleTimer);
        }

        idleTimer = setTimeout(() => {
            logout(true);
        }, IDLE_TIMEOUT_MS);
    }

    function startIdleWatch() {
        if (typeof window === 'undefined' || activityBound) {
            return;
        }

        ACTIVITY_EVENTS.forEach((eventName) => {
            window.addEventListener(eventName, onUserActivity, { passive: true });
        });
        activityBound = true;
        resetIdleTimer();
    }

    function stopIdleWatch() {
        if (idleTimer) {
            clearTimeout(idleTimer);
            idleTimer = null;
        }

        if (!activityBound || typeof window === 'undefined') {
            return;
        }

        ACTIVITY_EVENTS.forEach((eventName) => {
            window.removeEventListener(eventName, onUserActivity);
        });
        activityBound = false;
    }

    async function login(email, password) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await api.post('/auth/login', { email, password });
            const payload = data.data ?? data;

            token.value = payload.userToken;
            setBaseUser(payload.user);
            activeModule.value = null;
            workspace.value = payload.workspace ?? payload.user?.workspace;
            localStorage.setItem('userToken', payload.userToken);
            startIdleWatch();

            return workspace.value;
        } catch (err) {
            error.value = extractLoginError(err);
            throw err;
        } finally {
            loading.value = false;
        }
    }

    function extractLoginError(err) {
        const payload = err.response?.data ?? {};
        const errors = payload.errors ?? payload.data ?? {};

        const candidates = [
            errors?.activated,
            errors?.message,
            payload?.message,
            errors?.password,
            errors?.email,
        ];

        for (const candidate of candidates) {
            if (Array.isArray(candidate) && candidate.length) {
                return String(candidate[0]);
            }

            if (typeof candidate === 'string' && candidate.trim()) {
                return candidate;
            }
        }

        return 'Identifiants incorrects';
    }

    async function fetchUser() {
        if (!token.value) {
            return;
        }

        try {
            const { data } = await api.get('/auth/data');
            const payload = data.data ?? data;

            setBaseUser(payload.user ?? payload);
            workspace.value = payload.workspace ?? payload.user?.workspace;
            startIdleWatch();
        } catch {
            clearSessionLocally();
        }
    }

    async function changePassword({ current_password, new_password, new_password_confirmation }) {
        const { data } = await api.put('/users/update-password', {
            current_password,
            new_password,
            new_password_confirmation,
        });
        const payload = data.data ?? data;
        const nextUser = payload.user ?? baseUser.value;

        if (nextUser) {
            setBaseUser({
                ...nextUser,
                password_change_required: false,
            });
        } else if (baseUser.value) {
            setBaseUser({
                ...baseUser.value,
                password_change_required: false,
            });
        }

        return payload;
    }

    async function logout(redirectToLogin = false) {
        try {
            if (token.value) {
                await api.delete('/auth/logout');
            }
        } catch {
            // ignore logout errors
        } finally {
            clearSessionLocally();

            if (redirectToLogin && window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
    }

    return {
        user,
        baseUser,
        activeModule,
        workspace,
        token,
        loading,
        error,
        isAuthenticated,
        mustChangePassword,
        login,
        fetchUser,
        changePassword,
        logout,
        setActiveModule,
        startIdleWatch,
        stopIdleWatch,
        clearSessionLocally,
    };
});
