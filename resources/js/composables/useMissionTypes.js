import { computed, ref } from 'vue';
import api from '../api/client';

const missionTypes = ref([]);
const managedMissionTypes = ref([]);
const loading = ref(false);
const manageLoading = ref(false);
const loaded = ref(false);

function extractList(data) {
    if (Array.isArray(data?.data?.data)) return data.data.data;
    if (Array.isArray(data?.data)) return data.data;
    if (Array.isArray(data)) return data;
    return [];
}

export function filterMissionTypesForProfile(types, profile, currentValue = null) {
    const list = Array.isArray(types) ? types : [];

    const allowed = profile === 'super_admin'
        ? list.filter((type) => type.is_active !== false)
        : list.filter((type) => type.is_active !== false && (type.profiles ?? []).includes(profile));

    if (currentValue && !allowed.some((type) => type.value === currentValue)) {
        const current = list.find((type) => type.value === currentValue);
        if (current) {
            return [...allowed, current];
        }
    }

    return allowed;
}

export function useMissionTypes() {
    const types = computed(() => missionTypes.value);

    async function loadMissionTypes(force = false) {
        if (loaded.value && !force) {
            return missionTypes.value;
        }

        loading.value = true;

        try {
            const { data } = await api.get('/mission-types');
            missionTypes.value = extractList(data);
            loaded.value = true;
            return missionTypes.value;
        } finally {
            loading.value = false;
        }
    }

    async function loadManagedMissionTypes() {
        manageLoading.value = true;

        try {
            const { data } = await api.get('/mission-types/manage');
            managedMissionTypes.value = extractList(data);
            loaded.value = false;
            return managedMissionTypes.value;
        } finally {
            manageLoading.value = false;
        }
    }

    function getTypesForProfile(profile, currentValue = null) {
        return filterMissionTypesForProfile(missionTypes.value, profile, currentValue);
    }

    function findType(value) {
        return missionTypes.value.find((type) => type.value === value)
            ?? managedMissionTypes.value.find((type) => type.value === value);
    }

    async function createMissionType(payload) {
        const { data } = await api.post('/mission-types', payload);
        await loadManagedMissionTypes();
        await loadMissionTypes(true);
        return data?.data ?? data;
    }

    async function updateMissionType(id, payload) {
        const { data } = await api.put(`/mission-types/${id}`, payload);
        await loadManagedMissionTypes();
        await loadMissionTypes(true);
        return data?.data ?? data;
    }

    async function deleteMissionType(id) {
        await api.delete(`/mission-types/${id}`);
        await loadManagedMissionTypes();
        await loadMissionTypes(true);
    }

    return {
        types,
        managedTypes: computed(() => managedMissionTypes.value),
        loading: computed(() => loading.value),
        manageLoading: computed(() => manageLoading.value),
        loadMissionTypes,
        loadManagedMissionTypes,
        getTypesForProfile,
        findType,
        createMissionType,
        updateMissionType,
        deleteMissionType,
    };
}
