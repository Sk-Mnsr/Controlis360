import { ref } from 'vue';
import { defineStore } from 'pinia';
import { useRouter } from 'vue-router';

export const useCartographieStore = defineStore('cartographie', () => {
    const selectedDepartment = ref('DASHBOARD');
    const selectedEntityCode = ref(null);
    const departmentEntities = ref([]);
    const statusMessage = ref('');

    function resetDashboard() {
        selectedDepartment.value = 'DASHBOARD';
        selectedEntityCode.value = null;
        statusMessage.value = '';
    }

    function selectDepartment(dept) {
        selectedDepartment.value = dept;
        statusMessage.value = '';
    }

    function selectEntity(entity) {
        selectedDepartment.value = entity.name;
        selectedEntityCode.value = entity.code;
        statusMessage.value = '';
    }

    function setDepartmentEntities(entities) {
        departmentEntities.value = entities;
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
        departmentEntities,
        statusMessage,
        resetDashboard,
        selectDepartment,
        selectEntity,
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
        cartographie.resetDashboard();
        router.push({ name: 'cartographie.home' });
    }

    function selectDepartmentEntity(entity) {
        cartographie.selectEntity(entity);
        router.push({
            name: 'cartographie.departement-analyse',
            params: { code: entity.code },
        });
    }

    return {
        cartographie,
        navigateMethodology,
        goToDashboard,
        selectDepartmentEntity,
    };
}
