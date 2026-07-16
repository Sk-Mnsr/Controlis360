<template>
    <div class="flex h-screen overflow-hidden bg-slate-50 text-slate-900">
        <aside
            v-if="!hideSidebar"
            class="flex h-screen shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white"
            :class="activeModule ? 'w-72' : 'w-64'"
        >
            <div class="shrink-0 border-b border-slate-200 px-5 py-5">
                <img
                    :src="logoUrl"
                    alt="COFINA — Compagnie Financière Africaine"
                    class="h-14 w-auto max-w-full object-contain object-left"
                />
                <p v-if="isPortal" class="mt-2.5 text-sm font-medium text-slate-600">Controlis360</p>
                <p v-else-if="activeModule" class="mt-2.5 text-sm font-medium text-slate-600">{{ activeModule.name }}</p>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <RouterLink
                    v-if="isPortal"
                    class="nav-link nav-link-active"
                    :to="{ name: 'portal' }"
                >
                    Modules
                </RouterLink>

                <template v-else-if="activeModule?.slug === 'cartographie'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
                    </RouterLink>

                    <button
                        type="button"
                        class="nav-link nav-dashboard"
                        :class="{ 'nav-link-active': isDashboardActive }"
                        @click="goToDashboard"
                    >
                        Dashboard
                    </button>

                    <div class="nav-group">
                        <p class="nav-group-label" :class="{ 'nav-group-label-active': isMethodologySection }">
                            Méthodologie
                        </p>
                        <div class="nav-group-children">
                            <template v-for="item in methodologyItems" :key="item.id">
                                <RouterLink
                                    v-if="item.slug"
                                    class="nav-sublink"
                                    :class="{ 'nav-sublink-active': isMethodologyItemActive(item) }"
                                    :to="{ name: 'cartographie.methodology.show', params: { slug: item.slug } }"
                                >
                                    {{ item.label }}
                                </RouterLink>
                                <RouterLink
                                    v-else-if="item.route"
                                    class="nav-sublink"
                                    :class="{ 'nav-sublink-active': route.name === item.route }"
                                    :to="{ name: item.route }"
                                >
                                    {{ item.label }}
                                </RouterLink>
                                <button
                                    v-else
                                    type="button"
                                    class="nav-sublink nav-sublink-btn"
                                    @click="navigateMethodology(item)"
                                >
                                    {{ item.label }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="nav-group">
                        <p class="nav-group-label" :class="{ 'nav-group-label-active': isSaisieSection }">
                            Saisie
                        </p>
                        <div class="nav-group-children">
                            <RouterLink
                                v-if="canSaisirRisques"
                                class="nav-sublink"
                                :class="{ 'nav-sublink-active': route.name === 'cartographie.saisie-risques' }"
                                :to="{ name: 'cartographie.saisie-risques' }"
                            >
                                Nouvelle ligne
                            </RouterLink>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button
                            type="button"
                            class="nav-group-toggle"
                            :class="{ 'nav-group-toggle-active': isDepartmentsSection }"
                            :aria-expanded="departmentsOpen"
                            @click="departmentsOpen = !departmentsOpen"
                        >
                            <span>Départements</span>
                            <svg
                                class="nav-group-chevron"
                                :class="{ 'nav-group-chevron-open': departmentsOpen }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="departmentsOpen" class="nav-group-children">
                            <p v-if="entitiesLoading" class="nav-sublink nav-dept-loading">Chargement...</p>
                            <p v-else-if="!cartographie.departmentEntities.length" class="nav-sublink nav-dept-loading">Aucun département</p>
                            <button
                                v-for="entity in cartographie.departmentEntities"
                                :key="`${entity.environment_id ?? 'env'}-${entity.id}`"
                                type="button"
                                class="nav-sublink nav-sublink-btn nav-dept"
                                :class="{ 'nav-sublink-active': isEntityActive(entity) }"
                                @click="selectDepartmentEntity(entity)"
                            >
                                {{ entityNavLabel(entity) }}
                            </button>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button
                            type="button"
                            class="nav-group-toggle"
                            :class="{ 'nav-group-toggle-active': isAgenciesSection }"
                            :aria-expanded="agenciesOpen"
                            @click="agenciesOpen = !agenciesOpen"
                        >
                            <span>Agences</span>
                            <svg
                                class="nav-group-chevron"
                                :class="{ 'nav-group-chevron-open': agenciesOpen }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="agenciesOpen" class="nav-group-children">
                            <p v-if="entitiesLoading" class="nav-sublink nav-dept-loading">Chargement...</p>
                            <p v-else-if="!cartographie.agencyEntities.length" class="nav-sublink nav-dept-loading">Aucune agence</p>
                            <button
                                v-for="entity in cartographie.agencyEntities"
                                :key="`${entity.environment_id ?? 'env'}-${entity.id}`"
                                type="button"
                                class="nav-sublink nav-sublink-btn nav-dept"
                                :class="{ 'nav-sublink-active': isEntityActive(entity) }"
                                @click="selectDepartmentEntity(entity)"
                            >
                                {{ entityNavLabel(entity) }}
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="nav-cartographie"
                        :class="{ 'nav-cartographie-active': isCartographieSection }"
                        @click="openCartographie"
                    >
                        Cartographie
                    </button>
                </template>

                <template v-else-if="activeModule?.slug === 'audit'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': route.name === 'audit.home' }"
                        :to="{ name: 'audit.home' }"
                    >
                        Modules
                    </RouterLink>

                    <RouterLink
                        v-if="showRegulatorNav"
                        class="nav-link"
                        :class="{ 'nav-link-active': isAuditRegulatorSection }"
                        :to="{ name: 'audit.regulator' }"
                    >
                        Régulateur
                    </RouterLink>

                    <template v-if="!isRegulatorOnly">
                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isAuditMissionsSection }"
                            :to="{ name: 'audit.missions' }"
                        >
                        Dashboard
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isAuditHistorySection }"
                            :to="{ name: 'audit.missions.history' }"
                        >
                            Missions
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'audit.home' }"
                            :to="{ name: 'audit.home' }"
                        >
                            Historiques
                        </RouterLink>

                        <RouterLink
                            v-if="canCreateMission"
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'audit.parametrage' }"
                            :to="{ name: 'audit.parametrage' }"
                        >
                            Paramétrage
                        </RouterLink>
                    </template>
                </template>

                <template v-else-if="activeModule?.slug === 'conformite'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': route.name === 'conformite.home' }"
                        :to="{ name: 'conformite.home' }"
                    >
                        Accueil
                    </RouterLink>

                    <RouterLink
                        v-if="canManageConformiteSaisie"
                        class="nav-link"
                        :class="{ 'nav-link-active': isConformiteSaisieSection }"
                        :to="{ name: 'conformite.reporting.create' }"
                    >
                        Saisie
                    </RouterLink>

                    <RouterLink
                        v-if="canManageConformiteSaisie"
                        class="nav-link"
                        :class="{ 'nav-link-active': isConformiteHistorySection }"
                        :to="{ name: 'conformite.reporting.history' }"
                    >
                        Historique
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': isConformiteReceptionSection }"
                        :to="{ name: 'conformite.reporting.reception' }"
                    >
                        Réception
                    </RouterLink>
                </template>

                <template v-else>
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
                    </RouterLink>

                    <RouterLink
                        v-if="auth.user?.profile === 'super_admin'"
                        class="nav-link"
                        :class="{ 'nav-link-active': isEnvironmentsSection }"
                        :to="{ name: 'environments' }"
                    >
                        Environnements
                    </RouterLink>
                    <RouterLink
                        v-else-if="auth.user?.profile === 'admin' && adminEnvironmentIds.length"
                        class="nav-link"
                        :to="adminEnvironmentRoute"
                        active-class="nav-link-active"
                    >
                        {{ adminEnvironmentIds.length > 1 ? 'Mes environnements' : 'Mon environnement' }}
                    </RouterLink>

                    <div v-if="canManageUsers" class="nav-group">
                        <p class="nav-group-label" :class="{ 'nav-group-label-active': isUsersSection }">
                            Utilisateurs
                        </p>
                        <div class="nav-group-children">
                            <RouterLink
                                class="nav-sublink"
                                :class="{ 'nav-sublink-active': isUsersCreateSection }"
                                :to="{ name: 'users.create' }"
                            >
                                Nouveau
                            </RouterLink>
                            <RouterLink
                                class="nav-sublink"
                                :class="{ 'nav-sublink-active': isUsersHistorySection }"
                                :to="{ name: 'users.history' }"
                            >
                                Historiques
                            </RouterLink>
                        </div>
                    </div>

                    <RouterLink
                        v-if="canManageUsers"
                        class="nav-link"
                        :class="{ 'nav-link-active': isEntitiesSection }"
                        :to="{ name: 'entities.members' }"
                    >
                        Entités
                    </RouterLink>
                </template>
            </nav>

            <div class="shrink-0 border-t border-slate-200 px-4 py-4">
                <div class="mb-3">
                    <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ userRoleLabel }}</p>
                </div>

                <button
                    type="button"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-100"
                    @click="handleLogout"
                >
                    Déconnexion
                </button>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <main
                class="min-h-0 flex-1"
                :class="[
                    isFullBleedPage ? 'flex flex-col' : 'p-6 lg:p-8',
                    isConformiteSaisieSection ? 'overflow-hidden' : 'overflow-y-auto',
                ]"
            >
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { methodologyItems } from '../config/cartographie-nav';
import { getModuleFromRoute } from '../config/modules';
import { canCreateMission as userCanCreateMission, isRegulatorProfile } from '../config/module-access';
import { useCartographieNavigation } from '../stores/cartographie';
import { useAuthStore } from '../stores/auth';
import { useCartographiePermissions } from '../composables/useCartographiePermissions';
import api from '../api/client';

const auth = useAuthStore();
const { canCreateRiskRow } = useCartographiePermissions();
const canCreateMission = computed(() => userCanCreateMission(auth.user));
const route = useRoute();
const router = useRouter();
const { cartographie, navigateMethodology, goToDashboard, selectDepartmentEntity } = useCartographieNavigation();

const logoUrl = '/logo_Cofina.png';
const isPortal = computed(() => route.name === 'portal');
const activeModule = computed(() => getModuleFromRoute(route));
const isFullBleedPage = computed(() =>
    route.name === 'cartographie.home'
    || route.name === 'cartographie.cartographie'
    || route.name === 'cartographie.methodology.show'
    || route.name === 'cartographie.departement-analyse'
    || route.name === 'conformite.reporting.create'
    || route.name === 'conformite.reporting.edit',
);
const hideSidebar = computed(() => route.name === 'audit.missions.show');
const isCartographieSection = computed(() => route.name === 'cartographie.cartographie');
const isDashboardActive = computed(() =>
    (route.name === 'cartographie.home' && cartographie.selectedDepartment === 'DASHBOARD')
    || route.name === 'cartographie.departement-dashboard',
);
const isMethodologySection = computed(() => route.name === 'cartographie.methodology.show');
const isSaisieSection = computed(() => route.name === 'cartographie.saisie-risques');
const isDepartmentsSection = computed(() =>
    (route.name === 'cartographie.departement-analyse'
        || route.name === 'cartographie.departement-dashboard'
        || route.name === 'cartographie.departement-historique')
    && activeEntityType.value === 'department',
);
const isAgenciesSection = computed(() =>
    (route.name === 'cartographie.departement-analyse'
        || route.name === 'cartographie.departement-dashboard'
        || route.name === 'cartographie.departement-historique')
    && activeEntityType.value === 'agency',
);
const isEnvironmentsSection = computed(() => route.path.startsWith('/environments'));
const isUsersSection = computed(() => route.path.startsWith('/users'));
const isEntitiesSection = computed(() => route.name === 'entities.members');
const isUsersCreateSection = computed(() => route.name === 'users.create');
const isUsersHistorySection = computed(() => route.name === 'users.history' || route.name === 'users.edit');
const isAuditMissionsSection = computed(() =>
    route.name === 'audit.missions'
    || route.name === 'audit.missions.create'
    || route.name === 'audit.missions.edit'
    || (route.name === 'audit.missions.show' && route.query.from === 'missions'),
);
const isAuditHistorySection = computed(() =>
    route.name === 'audit.missions.history'
    || route.name === 'audit.missions.history.byType'
    || route.name === 'audit.missions.recommendation.create'
    || route.name === 'audit.missions.recommendation.edit'
    || (route.name === 'audit.missions.show' && route.query.from === 'history'),
);
const isAuditRegulatorSection = computed(() =>
    route.name === 'audit.regulator'
    || route.name === 'audit.regulator.show',
);
const isConformiteSaisieSection = computed(() =>
    route.name === 'conformite.reporting.create'
    || route.name === 'conformite.reporting.edit',
);
const isConformiteHistorySection = computed(() => route.name === 'conformite.reporting.history');
const isConformiteReceptionSection = computed(() =>
    route.name === 'conformite.reporting.reception'
    || route.name === 'conformite.reporting.reception.show',
);
const canManageConformiteSaisie = computed(() =>
    ['super_admin', 'conformite'].includes(auth.user?.profile),
);
const isRegulatorOnly = computed(() => auth.user?.profile === 'regulateur');
const showRegulatorNav = computed(() => isRegulatorProfile(auth.user?.profile));
const canManageUsers = computed(() => ['super_admin', 'admin'].includes(auth.user?.profile));
const departmentsOpen = ref(false);
const agenciesOpen = ref(false);
const entitiesLoading = ref(false);

const activeEntityType = computed(() => {
    if (route.name !== 'cartographie.departement-analyse'
        && route.name !== 'cartographie.departement-dashboard'
        && route.name !== 'cartographie.departement-historique') {
        return null;
    }

    const entity = cartographie.navigationEntities.find((item) => item.code === route.params.code);
    return entity?.type ?? null;
});

function normalizeEntitiesPayload(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }

    return [];
}

async function loadNavigationEntities() {
    if (!activeModule.value || activeModule.value.slug !== 'cartographie') {
        return;
    }

    if (entitiesLoading.value) {
        return;
    }

    entitiesLoading.value = true;

    try {
        const { data } = await api.get('/referentials/entities-departments');
        cartographie.setNavigationEntities(normalizeEntitiesPayload(data));
    } catch {
        cartographie.setNavigationEntities([]);
    } finally {
        entitiesLoading.value = false;
    }
}

watch(activeModule, (module) => {
    if (module?.slug === 'cartographie') {
        loadNavigationEntities();
    }
}, { immediate: true });

watch(isDepartmentsSection, (active) => {
    if (active) {
        departmentsOpen.value = true;
    }
}, { immediate: true });

watch(isAgenciesSection, (active) => {
    if (active) {
        agenciesOpen.value = true;
    }
}, { immediate: true });

function isMethodologyItemActive(item) {
    return route.name === 'cartographie.methodology.show' && route.params.slug === item.slug;
}

function isEntityActive(entity) {
    const onEntityRoute = route.name === 'cartographie.departement-analyse'
        || route.name === 'cartographie.departement-dashboard'
        || route.name === 'cartographie.departement-historique';

    if (!onEntityRoute || route.params.code !== entity.code) {
        return false;
    }

    if (cartographie.selectedEntityId) {
        return cartographie.selectedEntityId === entity.id;
    }

    const routeEnvironment = route.query.environment;
    const entityEnvironment = entity.environment?.code;

    if (routeEnvironment && entityEnvironment) {
        return routeEnvironment === entityEnvironment;
    }

    return true;
}

function entityNavLabel(entity) {
    const user = auth.user;

    if (user?.profile === 'super_admin' && !user?.environment_id && entity.environment?.code) {
        return `${entity.environment.code} — ${entity.name}`;
    }

    return entity.name;
}

function openCartographie() {
    cartographie.statusMessage = '';
    cartographie.resetDashboard();

    const environment = cartographie.navigationEntities[0]?.environment?.code;

    router.push({
        name: 'cartographie.cartographie',
        query: environment ? { environment } : {},
    });
}

const userRoleLabel = computed(() => {
    const user = auth.user;
    if (!user) return '';

    if (user.controle_role_fr) {
        return `${user.profile_fr} — ${user.controle_role_fr}`;
    }

    if (user.audit_role_fr) {
        return `${user.profile_fr} — ${user.audit_role_fr}`;
    }

    if (user.metier_role_fr) {
        return `${user.profile_fr} — ${user.metier_role_fr}`;
    }

    return user.profile_fr ?? '';
});

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<style scoped>
.nav-link {
    display: block;
    border-radius: 0.5rem;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    color: #475569;
    transition: background-color 0.15s, color 0.15s;
}

.nav-link:hover {
    background-color: #f8fafc;
    color: #0f172a;
}

.nav-link-active {
    background-color: #ecfdf5;
    color: #047857;
    font-weight: 600;
}

.nav-back {
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.nav-dashboard {
    width: 100%;
    text-align: left;
    border: none;
    background: transparent;
    cursor: pointer;
}

.nav-sublink-btn {
    width: 100%;
    text-align: left;
    border: none;
    background: transparent;
    cursor: pointer;
}

.nav-dept-loading {
    cursor: default;
    color: #94a3b8;
}

.nav-dept {
    font-size: 0.75rem;
    line-height: 1.35;
}

.nav-cartographie {
    display: block;
    width: 100%;
    margin-top: 0.75rem;
    border: none;
    border-radius: 0.5rem;
    background: linear-gradient(180deg, #16a34a 0%, #15803d 100%);
    padding: 0.7rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #ffffff;
    cursor: pointer;
    transition: opacity 0.15s;
}

.nav-cartographie:hover {
    opacity: 0.92;
}

.nav-cartographie-active {
    box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.85);
}

.nav-group {
    margin-top: 0.25rem;
}

.nav-group-label {
    padding: 0.5rem 0.75rem 0.35rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
}

.nav-group-label-active {
    color: #047857;
}

.nav-group-toggle {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    border: none;
    background: transparent;
    padding: 0.5rem 0.75rem 0.35rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
    cursor: pointer;
    transition: color 0.15s;
}

.nav-group-toggle:hover {
    color: #64748b;
}

.nav-group-toggle-active {
    color: #047857;
}

.nav-group-chevron {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    transition: transform 0.2s;
}

.nav-group-chevron-open {
    transform: rotate(180deg);
}

.nav-group-children {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    padding-left: 0.5rem;
}

.nav-sublink {
    display: block;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #475569;
    transition: background-color 0.15s, color 0.15s;
}

.nav-sublink:hover {
    background-color: #f8fafc;
    color: #0f172a;
}

.nav-sublink-active {
    background-color: #ecfdf5;
    color: #047857;
    font-weight: 600;
}
</style>
