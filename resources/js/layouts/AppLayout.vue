<template>
    <div class="flex h-screen overflow-hidden bg-slate-50 text-slate-900">
        <aside
            v-if="!hideSidebar"
            class="flex h-screen shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white"
            :class="[
                activeModule ? 'w-64 sm:w-72' : 'w-56 sm:w-64',
                'max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:z-40 max-lg:transition-transform',
                mobileNavOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full',
            ]"
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
                        class="nav-link nav-cartographie"
                        :class="{ 'nav-cartographie-active': isCartographieSection }"
                        @click="openCartographie"
                    >
                        Cartographie
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
                                v-if="canCreateRiskRow"
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
                </template>

                <template v-else-if="activeModule?.slug === 'audit'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
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
                            :class="{ 'nav-link-active': isAuditDashboardSection }"
                            :to="{ name: 'audit.dashboard' }"
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

                <template v-else-if="activeModule?.slug === 'gouvernance-it'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': route.name === 'gouvernance-it.home' }"
                        :to="{ name: 'gouvernance-it.home' }"
                    >
                        Accueil
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': isGovStratSection }"
                        :to="{ name: 'gouvernance-it.govstrat-itr' }"
                    >
                        GovStrat IT-R
                    </RouterLink>
                </template>

                <template v-else>
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }">
                        ← Tous les modules
                    </RouterLink>

                    <RouterLink
                        v-if="platformProfile === 'super_admin' || platformProfile === 'admin'"
                        class="nav-link"
                        :class="{ 'nav-link-active': isEnvironmentsSection }"
                        :to="platformProfile === 'admin' ? adminEnvironmentRoute : { name: 'environments' }"
                    >
                        {{
                            platformProfile === 'admin' && adminEnvironmentIds.length <= 1
                                ? 'Mon environnement'
                                : (platformProfile === 'admin' ? 'Mes environnements' : 'Environnements')
                        }}
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
            <header
                v-if="!hideSidebar"
                class="flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:hidden"
            >
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    :aria-expanded="mobileNavOpen"
                    aria-label="Ouvrir le menu"
                    @click="mobileNavOpen = !mobileNavOpen"
                >
                    Menu
                </button>
                <p class="truncate text-sm font-medium text-slate-700">
                    {{ activeModule?.name || 'Controlis360' }}
                </p>
            </header>

            <div
                v-if="mobileNavOpen && !hideSidebar"
                class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
                @click="mobileNavOpen = false"
            />

            <main
                class="min-h-0 min-w-0 flex-1"
                :class="[
                    isFullBleedPage ? 'flex flex-col' : 'p-4 sm:p-6 lg:p-8',
                    (isConformiteSaisieSection || isAnalyseFullBleed) ? 'overflow-hidden' : 'overflow-y-auto',
                ]"
            >
                <RouterView class="min-h-0 min-w-0 w-full flex-1" />
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
import { uniqueEnvironments } from '../utils/cartographyDashboard';
import api from '../api/client';

const auth = useAuthStore();
const { canCreateRiskRow } = useCartographiePermissions();
const canCreateMission = computed(() => userCanCreateMission(auth.baseUser ?? auth.user));
const route = useRoute();
const router = useRouter();
const { cartographie, navigateMethodology, selectDepartmentEntity } = useCartographieNavigation();

const logoUrl = '/logo_Cofina.png';
const isPortal = computed(() => route.name === 'portal');
const activeModule = computed(() => getModuleFromRoute(route));
const isFullBleedPage = computed(() =>
    route.name === 'cartographie.home'
    || route.name === 'cartographie.cartographie'
    || route.name === 'cartographie.methodology.show'
    || route.name === 'cartographie.departement-analyse'
    || route.name === 'cartographie.departement-dashboard'
    || route.name === 'cartographie.plus-gros-risques'
    || route.name === 'cartographie.definitions-objectifs'
    || route.name === 'cartographie.preambule'
    || route.name === 'cartographie.principes'
    || route.name === 'cartographie.echelle-pg'
    || route.name === 'cartographie.echelle-controle'
    || route.name === 'cartographie.matrice-risques'
    || route.name === 'cartographie.lexique'
    || route.name === 'conformite.reporting.create'
    || route.name === 'conformite.reporting.edit',
);
const isAnalyseFullBleed = computed(() => route.name === 'cartographie.departement-analyse');
const hideSidebar = computed(() =>
    route.name === 'audit.missions.show'
    || route.name === 'gouvernance-it.govstrat-itr'
    || route.name === 'gouvernance-it.task-activity'
    || route.name === 'gouvernance-it.centre-support'
    || route.name === 'gouvernance-it.systemes-reseaux'
    || route.name === 'gouvernance-it.base-donnees'
    || route.name === 'gouvernance-it.retroplanning',
);
const isCartographieSection = computed(() => route.name === 'cartographie.cartographie');
const isMethodologySection = computed(() => [
    'cartographie.methodology.show',
    'cartographie.definitions-objectifs',
    'cartographie.preambule',
    'cartographie.principes',
    'cartographie.echelle-pg',
    'cartographie.echelle-controle',
    'cartographie.matrice-risques',
    'cartographie.lexique',
    'cartographie.plus-gros-risques',
].includes(route.name));
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
const isUsersCreateSection = computed(() => route.name === 'users.create');
const isUsersHistorySection = computed(() => route.name === 'users.history' || route.name === 'users.edit');
const isAuditDashboardSection = computed(() =>
    route.name === 'audit.dashboard'
    || route.name === 'audit.missions'
    || (route.name === 'audit.missions.show' && route.query.from === 'dashboard'),
);
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
const platformProfile = computed(() => auth.baseUser?.profile ?? auth.user?.profile ?? null);
const canManageConformiteSaisie = computed(() =>
    ['super_admin', 'admin', 'conformite'].includes(platformProfile.value),
);
const isGovStratSection = computed(() =>
    route.name === 'gouvernance-it.govstrat-itr'
    || route.name === 'gouvernance-it.task-activity'
    || route.name === 'gouvernance-it.centre-support'
    || route.name === 'gouvernance-it.systemes-reseaux'
    || route.name === 'gouvernance-it.base-donnees',
);
const isRegulatorOnly = computed(() => platformProfile.value === 'regulateur');
const showRegulatorNav = computed(() => isRegulatorProfile(platformProfile.value));
const canManageUsers = computed(() => ['super_admin', 'admin'].includes(platformProfile.value));

const adminEnvironmentIds = computed(() => {
    const user = auth.baseUser ?? auth.user;
    if (Array.isArray(user?.environment_ids) && user.environment_ids.length) {
        return user.environment_ids.map((id) => Number(id)).filter((id) => !Number.isNaN(id));
    }

    return (user?.environments ?? [])
        .map((environment) => Number(environment.id))
        .filter((id) => !Number.isNaN(id));
});

const adminEnvironmentRoute = computed(() => {
    if (adminEnvironmentIds.value.length === 1) {
        return { name: 'environments.detail', params: { id: adminEnvironmentIds.value[0] } };
    }

    return { name: 'environments' };
});

const departmentsOpen = ref(false);
const agenciesOpen = ref(false);
const entitiesLoading = ref(false);
const mobileNavOpen = ref(false);

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

watch(() => route.fullPath, () => {
    mobileNavOpen.value = false;
});

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

    const environments = uniqueEnvironments(cartographie.navigationEntities);
    const environment = environments.length > 1
        ? 'all'
        : (environments[0]?.code ?? null);

    router.push({
        name: 'cartographie.cartographie',
        query: environment ? { environment } : {},
    });
}

const userRoleLabel = computed(() => {
    const user = auth.baseUser ?? auth.user;
    if (!user) return '';

    if (user.profile === 'admin' || user.profile === 'super_admin') {
        return user.profile_fr ?? '';
    }

    if (auth.user?.controle_role_fr) {
        return `${auth.user.profile_fr} — ${auth.user.controle_role_fr}`;
    }

    if (auth.user?.audit_role_fr) {
        return `${auth.user.profile_fr} — ${auth.user.audit_role_fr}`;
    }

    if (auth.user?.gouvernance_it_role_fr) {
        return `${auth.user.profile_fr} — ${auth.user.gouvernance_it_role_fr}`;
    }

    if (auth.user?.metier_role_fr) {
        return `${auth.user.profile_fr} — ${auth.user.metier_role_fr}`;
    }

    return auth.user?.profile_fr ?? user.profile_fr ?? '';
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
    background-color: #fef2f2;
    color: #c00000;
    font-weight: 600;
}

.nav-back {
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.nav-cartographie {
    display: block;
    width: 100%;
    margin-bottom: 0.35rem;
    border: none;
    border-radius: 0.5rem;
    background: linear-gradient(180deg, #c00000 0%, #9f0000 100%);
    padding: 0.7rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #ffffff;
    cursor: pointer;
    text-align: center;
    transition: opacity 0.15s;
}

.nav-cartographie:hover {
    opacity: 0.92;
}

.nav-cartographie-active {
    box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.85);
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
    color: #c00000;
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
    color: #c00000;
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
    background-color: #fef2f2;
    color: #c00000;
    font-weight: 600;
}
</style>
