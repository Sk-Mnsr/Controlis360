import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { canCreateOperationalRiskRow, canEditMethodology } from '../utils/cartographiePermissions';

export function useCartographiePermissions() {
    const auth = useAuthStore();

    const canEditMethodologyPermission = computed(() => canEditMethodology(auth.user));
    const canCreateRiskRow = computed(() => canCreateOperationalRiskRow(auth.user));

    return {
        canEditMethodology: canEditMethodologyPermission,
        canCreateRiskRow,
    };
}
