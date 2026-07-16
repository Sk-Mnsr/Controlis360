import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { useRouter } from 'vue-router';
import { entityRouteQuery } from '../utils/entityEnvironment';

export const useCartographieStore = defineStore('cartographie', () => {
    const selectedDepartment = ref('DASHBOARD');
    const selectedEntityCode = ref(null);
    const selectedEntityId = ref(null);
    const navigationEntities = ref([]);
    const statusMessage = ref('');

    const departmentEntities = computed(() =>
        navigationEntities.value.filter((entity) => entity.type === 'department'),
    );

    const agencyEntities = computed(() =>
        navigationEntities.value.filter((entity) => entity.type === 'agency'),
    );

    function resetDashboard() {
        selectedDepartment.value = 'DASHBOARD';
        selectedEntityCode.value = null;
        selectedEntityId.value = null;
        statusMessage.value = '';
    }

    function selectDepartment(dept) {
        selectedDepartment.value = dept;
        statusMessage.value = '';
    }

    function selectEntity(entity) {
        selectedDepartment.value = entity.name;
        selectedEntityCode.value = entity.code;
        selectedEntityId.value = entity.id;
        statusMessage.value = '';
    }

    function setNavigationEntities(entities) {
        const list = Array.isArray(entities) ? entities : [];
        const unique = new Map();

        for (const entity of list) {
            const key = entity?.id ?? `${entity?.environment_id ?? 'env'}-${entity?.code ?? entity?.name}`;
            if (!unique.has(key)) {
                unique.set(key, entity);
            }
        }

        navigationEntities.value = [...unique.values()];
    }

    /** @deprecated Utiliser setNavigationEntities */
    function setDepartmentEntities(entities) {
        setNavigationEntities(entities);
    }

    function openCartographie() {
        statusMessage.value = 'Cartographie des risques — module à venir (phase 2).';
    }

    function openMethodologyPlaceholder(label) {
        statusMessage.value = `« ${label} » — contenu à intégrer (phase 2).`;
    }

    return {
        selectedDepartment,
        selectedEntityCode,
        selectedEntityId,
        navigationEntities,
        departmentEntities,
        agencyEntities,
        statusMessage,
        resetDashboard,
        selectDepartment,
        selectEntity,
        setNavigationEntities,
        setDepartmentEntities,
        openCartographie,
        openMethodologyPlaceholder,
    };
});

export function useCartographieNavigation() {
    const router = useRouter();
    const cartographie = useCartographieStore();

    function navigateMethodology(item) {
        cartographie.statusMessage = '';

        if (item.slug) {
            router.push({ name: 'cartographie.methodology.show', params: { slug: item.slug } });
            return;
        }

        if (item.route) {
            router.push({ name: item.route });
            return;
        }

        cartographie.openMethodologyPlaceholder(item.label);
    }

    function goToDashboard() {
        if (cartographie.selectedEntityCode) {
            const entity = cartographie.navigationEntities.find(
                (item) => item.id === cartographie.selectedEntityId
                    || item.code === cartographie.selectedEntityCode,
            );

            cartographie.selectedDepartment = entity?.name ?? cartographie.selectedEntityCode;

            router.push({
                name: 'cartographie.departement-dashboard',
                params: { code: cartographie.selectedEntityCode },
                query: entityRouteQuery(entity),
            });

            return;
        }

        cartographie.resetDashboard();
        router.push({ name: 'cartographie.home' });
    }

    function selectDepartmentEntity(entity) {
        cartographie.selectEntity(entity);
        router.push({
            name: 'cartographie.departement-analyse',
            params: { code: entity.code },
            query: entity.environment?.code ? { environment: entity.environment.code } : {},
        });
    }

    return {
        cartographie,
        navigateMethodology,
        goToDashboard,
        selectDepartmentEntity,
    };
}
