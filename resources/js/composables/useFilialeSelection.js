import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api/client';

function extractList(payload) {
    const root = payload?.data ?? payload;
    if (Array.isArray(root)) return root;
    if (Array.isArray(root?.data)) return root.data;
    return [];
}

export function environmentIdFromRoute(route) {
    const raw = Array.isArray(route.query.environment) ? route.query.environment[0] : route.query.environment;
    const id = Number(raw);
    return Number.isInteger(id) && id > 0 ? String(id) : '';
}

export function useFilialeSelection() {
    const route = useRoute();
    const router = useRouter();
    const environments = ref([]);
    const selectedEnvironmentId = ref('');

    const filialeQuery = computed(() => (
        selectedEnvironmentId.value ? { environment: selectedEnvironmentId.value } : {}
    ));

    async function loadEnvironments() {
        try {
            const { data } = await api.get('/environments', {
                params: { paginate: 'false', order_by_asc: 'name' },
            });
            environments.value = extractList(data);
        } catch {
            environments.value = [];
        }

        const fromRoute = environmentIdFromRoute(route);
        const known = environments.value.some((env) => String(env.id) === fromRoute);
        if (known) {
            selectedEnvironmentId.value = fromRoute;
            return;
        }

        selectedEnvironmentId.value = environments.value[0] ? String(environments.value[0].id) : '';
        if (selectedEnvironmentId.value) {
            await rememberEnvironment(selectedEnvironmentId.value);
        }
    }

    async function rememberEnvironment(environmentId) {
        const next = environmentId ? String(environmentId) : '';
        const current = environmentIdFromRoute(route);
        if (next === current) return;

        const query = { ...route.query };
        if (next) {
            query.environment = next;
        } else {
            delete query.environment;
        }
        await router.replace({ query });
    }

    async function onEnvironmentChange() {
        await rememberEnvironment(selectedEnvironmentId.value);
    }

    function routeWithFiliale(name) {
        return Object.keys(filialeQuery.value).length
            ? { name, query: filialeQuery.value }
            : { name };
    }

    watch(
        () => route.query.environment,
        () => {
            const fromRoute = environmentIdFromRoute(route);
            if (!fromRoute) {
                selectedEnvironmentId.value = '';
                return;
            }
            if (environments.value.some((env) => String(env.id) === fromRoute)) {
                selectedEnvironmentId.value = fromRoute;
            }
        },
    );

    return {
        environments,
        selectedEnvironmentId,
        filialeQuery,
        loadEnvironments,
        onEnvironmentChange,
        routeWithFiliale,
    };
}
