<template>
    <div class="flex h-screen overflow-hidden bg-slate-50 text-slate-900">
        <aside
            v-if="!hideSidebar"
            class="sidebar flex h-screen shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white transition-[width] duration-200 ease-out"
            :class="[
                sidebarCollapsed
                    ? 'w-64 sm:w-72 lg:w-[4.75rem]'
                    : (activeModule ? 'w-64 sm:w-72' : 'w-56 sm:w-64'),
                sidebarCollapsed ? 'sidebar--collapsed' : '',
                'max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:z-40 max-lg:transition-transform',
                mobileNavOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full',
            ]"
        >
            <div class="sidebar-brand shrink-0 border-b border-slate-200 px-3 py-3">
                <div class="flex items-center gap-2">
                    <img
                        :src="logoUrl"
                        alt="COFINA — Compagnie Financière Africaine"
                        class="sidebar-logo object-contain object-left"
                    />
                    <button
                        type="button"
                        class="sidebar-rail-btn ml-auto"
                        :aria-label="sidebarCollapsed ? 'Développer le menu' : 'Réduire le menu'"
                        :title="sidebarCollapsed ? 'Développer le menu' : 'Réduire le menu'"
                        @click="toggleSidebarRail"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="h-4 w-4 transition-transform duration-200"
                            :class="{ 'rotate-180': sidebarCollapsed }"
                            aria-hidden="true"
                        >
                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <p v-if="!sidebarCollapsed && isPortal" class="mt-2.5 px-1 text-sm font-medium text-slate-600">Controlis360</p>
                <p v-else-if="!sidebarCollapsed && activeModule" class="mt-2.5 px-1 text-sm font-medium text-slate-600">{{ activeModule.name }}</p>
            </div>

            <nav class="sidebar-nav min-h-0 flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-2 py-3">
                <RouterLink
                    v-if="isPortal"
                    class="nav-link nav-link-active"
                    :to="{ name: 'portal' }"
                    title="Modules"
                >
                    <span class="nav-ico" aria-hidden="true">▦</span>
                    <span class="nav-label">Modules</span>
                </RouterLink>

                <template v-else-if="activeModule?.slug === 'cartographie'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }" title="Tous les modules">
                        <span class="nav-ico" aria-hidden="true">←</span>
                        <span class="nav-label">Tous les modules</span>
                    </RouterLink>

                    <button
                        type="button"
                        class="nav-link nav-cartographie"
                        :class="{ 'nav-cartographie-active': isCartographieSection }"
                        title="Cartographie"
                        @click="openCartographie"
                    >
                        <span class="nav-ico" aria-hidden="true">◉</span>
                        <span class="nav-label">Cartographie</span>
                    </button>

                    <div class="nav-group">
                        <p class="nav-group-label" :class="{ 'nav-group-label-active': isMethodologySection }">
                            <span class="nav-label">Méthodologie</span>
                        </p>
                        <div class="nav-group-children">
                            <template v-for="item in methodologyItems" :key="item.id">
                                <RouterLink
                                    v-if="item.slug"
                                    class="nav-sublink"
                                    :class="{ 'nav-sublink-active': isMethodologyItemActive(item) }"
                                    :to="{ name: 'cartographie.methodology.show', params: { slug: item.slug } }"
                                    :title="item.label"
                                >
                                    <span class="nav-ico nav-ico-letter" aria-hidden="true">{{ itemInitial(item.label) }}</span>
                                    <span class="nav-label">{{ item.label }}</span>
                                </RouterLink>
                                <RouterLink
                                    v-else-if="item.route"
                                    class="nav-sublink"
                                    :class="{ 'nav-sublink-active': route.name === item.route }"
                                    :to="{ name: item.route }"
                                    :title="item.label"
                                >
                                    <span class="nav-ico nav-ico-letter" aria-hidden="true">{{ itemInitial(item.label) }}</span>
                                    <span class="nav-label">{{ item.label }}</span>
                                </RouterLink>
                                <button
                                    v-else
                                    type="button"
                                    class="nav-sublink nav-sublink-btn"
                                    :title="item.label"
                                    @click="navigateMethodology(item)"
                                >
                                    <span class="nav-ico nav-ico-letter" aria-hidden="true">{{ itemInitial(item.label) }}</span>
                                    <span class="nav-label">{{ item.label }}</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="nav-group">
                        <p class="nav-group-label" :class="{ 'nav-group-label-active': isSaisieSection }">
                            <span class="nav-label">Saisie</span>
                        </p>
                        <div class="nav-group-children">
                            <RouterLink
                                v-if="canCreateRiskRow"
                                class="nav-sublink"
                                :class="{ 'nav-sublink-active': route.name === 'cartographie.saisie-risques' }"
                                :to="{ name: 'cartographie.saisie-risques' }"
                                title="Nouvelle ligne"
                            >
                                <span class="nav-ico" aria-hidden="true">＋</span>
                                <span class="nav-label">Nouvelle ligne</span>
                            </RouterLink>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button
                            type="button"
                            class="nav-group-toggle"
                            :class="{ 'nav-group-toggle-active': isDepartmentsSection }"
                            :aria-expanded="departmentsOpen"
                            title="Départements"
                            @click="toggleDepartmentsSection"
                        >
                            <span class="nav-ico" aria-hidden="true">▣</span>
                            <span class="nav-label">Départements</span>
                            <svg
                                class="nav-group-chevron nav-label"
                                :class="{ 'nav-group-chevron-open': departmentsOpen }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="departmentsOpen" class="nav-group-children nav-group-children--entities">
                            <p v-if="entitiesLoading" class="nav-sublink nav-dept-loading">Chargement...</p>
                            <p v-else-if="!cartographie.departmentEntities.length" class="nav-sublink nav-dept-loading">Aucun département</p>
                            <button
                                v-for="entity in cartographie.departmentEntities"
                                :key="`${entity.environment_id ?? 'env'}-${entity.id}`"
                                type="button"
                                class="nav-sublink nav-sublink-btn nav-dept"
                                :class="{ 'nav-sublink-active': isEntityActive(entity) }"
                                :title="entityNavLabel(entity)"
                                @click="selectDepartmentEntity(entity)"
                            >
                                <span class="nav-label">{{ entityNavLabel(entity) }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button
                            type="button"
                            class="nav-group-toggle"
                            :class="{ 'nav-group-toggle-active': isAgenciesSection }"
                            :aria-expanded="agenciesOpen"
                            title="Agences"
                            @click="toggleAgenciesSection"
                        >
                            <span class="nav-ico" aria-hidden="true">⌖</span>
                            <span class="nav-label">Agences</span>
                            <svg
                                class="nav-group-chevron nav-label"
                                :class="{ 'nav-group-chevron-open': agenciesOpen }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="agenciesOpen" class="nav-group-children nav-group-children--entities">
                            <p v-if="entitiesLoading" class="nav-sublink nav-dept-loading">Chargement...</p>
                            <p v-else-if="!cartographie.agencyEntities.length" class="nav-sublink nav-dept-loading">Aucune agence</p>
                            <button
                                v-for="entity in cartographie.agencyEntities"
                                :key="`${entity.environment_id ?? 'env'}-${entity.id}`"
                                type="button"
                                class="nav-sublink nav-sublink-btn nav-dept"
                                :class="{ 'nav-sublink-active': isEntityActive(entity) }"
                                :title="entityNavLabel(entity)"
                                @click="selectDepartmentEntity(entity)"
                            >
                                <span class="nav-label">{{ entityNavLabel(entity) }}</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template v-else-if="activeModule?.slug === 'audit'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }" title="Tous les modules">
                        <span class="nav-ico" aria-hidden="true">←</span>
                        <span class="nav-label">Tous les modules</span>
                    </RouterLink>

                    <RouterLink
                        v-if="showRegulatorNav"
                        class="nav-link"
                        :class="{ 'nav-link-active': isAuditRegulatorSection }"
                        :to="{ name: 'audit.regulator' }"
                        title="Régulateur"
                    >
                        <span class="nav-ico" aria-hidden="true">◎</span>
                        <span class="nav-label">Régulateur</span>
                    </RouterLink>

                    <template v-if="!isRegulatorOnly">
                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isAuditDashboardSection }"
                            :to="{ name: 'audit.dashboard' }"
                            title="Dashboard"
                        >
                            <span class="nav-ico" aria-hidden="true">▦</span>
                            <span class="nav-label">Dashboard</span>
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isAuditHistorySection }"
                            :to="{ name: 'audit.missions.history' }"
                            title="Missions"
                        >
                            <span class="nav-ico" aria-hidden="true">☰</span>
                            <span class="nav-label">Missions</span>
                        </RouterLink>

                        <RouterLink
                            v-if="canCreateMission"
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'audit.parametrage' }"
                            :to="{ name: 'audit.parametrage' }"
                            title="Paramétrage"
                        >
                            <span class="nav-ico" aria-hidden="true">⚙</span>
                            <span class="nav-label">Paramétrage</span>
                        </RouterLink>
                    </template>
                </template>

                <template v-else-if="activeModule?.slug === 'conformite'">
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }" title="Tous les modules">
                        <span class="nav-ico" aria-hidden="true">←</span>
                        <span class="nav-label">Tous les modules</span>
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': route.name === 'conformite.home' }"
                        :to="{ name: 'conformite.home' }"
                        title="Accueil"
                    >
                        <span class="nav-ico" aria-hidden="true">⌂</span>
                        <span class="nav-label">Accueil</span>
                    </RouterLink>

                    <RouterLink
                        v-if="canManageConformiteSaisie"
                        class="nav-link"
                        :class="{ 'nav-link-active': isConformiteSaisieSection }"
                        :to="{ name: 'conformite.reporting.create' }"
                        title="Saisie"
                    >
                        <span class="nav-ico" aria-hidden="true">✎</span>
                        <span class="nav-label">Saisie</span>
                    </RouterLink>

                    <RouterLink
                        v-if="canManageConformiteSaisie"
                        class="nav-link"
                        :class="{ 'nav-link-active': isConformiteHistorySection }"
                        :to="{ name: 'conformite.reporting.history' }"
                        title="Historique"
                    >
                        <span class="nav-ico" aria-hidden="true">◷</span>
                        <span class="nav-label">Historique</span>
                    </RouterLink>

                    <RouterLink
                        class="nav-link"
                        :class="{ 'nav-link-active': isConformiteReceptionSection }"
                        :to="{ name: 'conformite.reporting.reception' }"
                        title="Réception"
                    >
                        <span class="nav-ico" aria-hidden="true">⬇</span>
                        <span class="nav-label">Réception</span>
                    </RouterLink>
                </template>

                <template v-else-if="activeModule?.slug === 'gouvernance-it'">
                    <template v-if="isCartographieApplicationsSection">
                        <RouterLink
                            class="nav-link nav-back"
                            :to="{ name: 'gouvernance-it.home' }"
                            title="Retour Gouvernance IT"
                        >
                            <span class="nav-ico" aria-hidden="true">←</span>
                            <span class="nav-label">Gouvernance IT</span>
                        </RouterLink>

                        <p class="nav-group-label nav-group-label-active">
                            <span class="nav-label">Cartographie applications</span>
                        </p>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'gouvernance-it.cartographie-applications' }"
                            :to="{ name: 'gouvernance-it.cartographie-applications' }"
                            title="Services IT"
                        >
                            <span class="nav-ico" aria-hidden="true">▤</span>
                            <span class="nav-label">Services IT</span>
                        </RouterLink>
                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'gouvernance-it.cartographie-applications.applications' }"
                            :to="{ name: 'gouvernance-it.cartographie-applications.applications' }"
                            title="Inventaire applications"
                        >
                            <span class="nav-ico" aria-hidden="true">☰</span>
                            <span class="nav-label">Applications</span>
                        </RouterLink>
                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'gouvernance-it.cartographie-applications.contrats' }"
                            :to="{ name: 'gouvernance-it.cartographie-applications.contrats' }"
                            title="Contrats IT"
                        >
                            <span class="nav-ico" aria-hidden="true">◎</span>
                            <span class="nav-label">Contrats IT</span>
                        </RouterLink>
                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'gouvernance-it.cartographie-applications.projets' }"
                            :to="{ name: 'gouvernance-it.cartographie-applications.projets' }"
                            title="Projets IT"
                        >
                            <span class="nav-ico" aria-hidden="true">▦</span>
                            <span class="nav-label">Projets IT</span>
                        </RouterLink>
                    </template>

                    <template v-else-if="isRegistreComptesSection">
                        <RouterLink
                            class="nav-link nav-back"
                            :to="{ name: 'gouvernance-it.home' }"
                            title="Retour Gouvernance IT"
                        >
                            <span class="nav-ico" aria-hidden="true">←</span>
                            <span class="nav-label">Gouvernance IT</span>
                        </RouterLink>

                        <RouterLink
                            class="nav-link nav-link-active"
                            :to="{ name: 'gouvernance-it.registre-comptes-generiques' }"
                            title="Registre de comptes génériques"
                        >
                            <span class="nav-ico" aria-hidden="true">☰</span>
                            <span class="nav-label">Comptes génériques</span>
                        </RouterLink>
                    </template>

                    <template v-else>
                        <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }" title="Tous les modules">
                            <span class="nav-ico" aria-hidden="true">←</span>
                            <span class="nav-label">Tous les modules</span>
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': route.name === 'gouvernance-it.home' }"
                            :to="{ name: 'gouvernance-it.home' }"
                            title="Accueil"
                        >
                            <span class="nav-ico" aria-hidden="true">⌂</span>
                            <span class="nav-label">Accueil</span>
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isGovStratSection }"
                            :to="{ name: 'gouvernance-it.govstrat-itr' }"
                            title="GovStrat IT-R"
                        >
                            <span class="nav-ico" aria-hidden="true">⚙</span>
                            <span class="nav-label">GovStrat IT-R</span>
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isCartographieApplicationsSection }"
                            :to="{ name: 'gouvernance-it.cartographie-applications' }"
                            title="Cartographie des applications"
                        >
                            <span class="nav-ico" aria-hidden="true">▤</span>
                            <span class="nav-label">Cartographie applications</span>
                        </RouterLink>

                        <RouterLink
                            class="nav-link"
                            :class="{ 'nav-link-active': isRegistreComptesSection }"
                            :to="{ name: 'gouvernance-it.registre-comptes-generiques' }"
                            title="Registre de comptes génériques"
                        >
                            <span class="nav-ico" aria-hidden="true">☰</span>
                            <span class="nav-label">Comptes génériques</span>
                        </RouterLink>
                    </template>
                </template>

                <template v-else>
                    <RouterLink class="nav-link nav-back" :to="{ name: 'portal' }" title="Tous les modules">
                        <span class="nav-ico" aria-hidden="true">←</span>
                        <span class="nav-label">Tous les modules</span>
                    </RouterLink>

                    <RouterLink
                        v-if="platformProfile === 'super_admin' || platformProfile === 'admin'"
                        class="nav-link"
                        :class="{ 'nav-link-active': isEnvironmentsSection }"
                        :to="platformProfile === 'admin' ? adminEnvironmentRoute : { name: 'environments' }"
                        :title="platformProfile === 'admin' && adminEnvironmentIds.length <= 1
                            ? 'Mon environnement'
                            : (platformProfile === 'admin' ? 'Mes environnements' : 'Environnements')"
                    >
                        <span class="nav-ico" aria-hidden="true">🌐</span>
                        <span class="nav-label">
                            {{
                                platformProfile === 'admin' && adminEnvironmentIds.length <= 1
                                    ? 'Mon environnement'
                                    : (platformProfile === 'admin' ? 'Mes environnements' : 'Environnements')
                            }}
                        </span>
                    </RouterLink>

                    <div v-if="canManageUsers" class="nav-group">
                        <p class="nav-group-label" :class="{ 'nav-group-label-active': isUsersSection }">
                            <span class="nav-label">Utilisateurs</span>
                        </p>
                        <div class="nav-group-children">
                            <RouterLink
                                class="nav-sublink"
                                :class="{ 'nav-sublink-active': isUsersCreateSection }"
                                :to="{ name: 'users.create' }"
                                title="Nouveau"
                            >
                                <span class="nav-ico" aria-hidden="true">＋</span>
                                <span class="nav-label">Nouveau</span>
                            </RouterLink>
                            <RouterLink
                                class="nav-sublink"
                                :class="{ 'nav-sublink-active': isUsersHistorySection }"
                                :to="{ name: 'users.history' }"
                                title="Historiques"
                            >
                                <span class="nav-ico" aria-hidden="true">☰</span>
                                <span class="nav-label">Historiques</span>
                            </RouterLink>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="sidebar-footer shrink-0 border-t border-slate-200 px-3 py-3">
                <div v-if="!sidebarCollapsed" class="mb-3 px-1">
                    <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ userRoleLabel }}</p>
                </div>

                <button
                    type="button"
                    class="sidebar-logout"
                    :title="sidebarCollapsed ? 'Déconnexion' : undefined"
                    @click="handleLogout"
                >
                    <span class="nav-ico" aria-hidden="true">⎋</span>
                    <span class="nav-label">Déconnexion</span>
                </button>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <header
                v-if="!hideSidebar"
                class="flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 py-2.5 lg:hidden"
            >
                <button
                    type="button"
                    class="sidebar-open-btn"
                    :aria-expanded="mobileNavOpen"
                    aria-label="Menu"
                    @click="mobileNavOpen = !mobileNavOpen"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                    </svg>
                    <span>Menu</span>
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
    || route.name === 'cartographie.plus-gros-risques'
    || route.name === 'cartographie.definitions-objectifs'
    || route.name === 'cartographie.preambule'
    || route.name === 'cartographie.principes'
    || route.name === 'cartographie.echelle-pg'
    || route.name === 'cartographie.echelle-controle'
    || route.name === 'cartographie.matrice-risques'
    || route.name === 'cartographie.lexique'
    || route.name === 'conformite.reporting.create'
    || route.name === 'conformite.reporting.edit'
    || route.name === 'gouvernance-it.cartographie-applications'
    || route.name === 'gouvernance-it.cartographie-applications.applications'
    || route.name === 'gouvernance-it.registre-comptes-generiques',
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
const isCartographieApplicationsSection = computed(() =>
    route.name === 'gouvernance-it.cartographie-applications'
    || route.name === 'gouvernance-it.cartographie-applications.applications'
    || route.name === 'gouvernance-it.cartographie-applications.contrats'
    || route.name === 'gouvernance-it.cartographie-applications.projets',
);
const isRegistreComptesSection = computed(() =>
    route.name === 'gouvernance-it.registre-comptes-generiques',
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

const SIDEBAR_COLLAPSED_KEY = 'controlis360.sidebarCollapsed';

const departmentsOpen = ref(false);
const agenciesOpen = ref(false);
const entitiesLoading = ref(false);
const mobileNavOpen = ref(false);
const sidebarCollapsed = ref(
    typeof localStorage !== 'undefined' && localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1',
);

function persistSidebarCollapsed(value) {
    sidebarCollapsed.value = value;
    try {
        localStorage.setItem(SIDEBAR_COLLAPSED_KEY, value ? '1' : '0');
    } catch {
        // ignore quota / private mode
    }
}

function toggleSidebarRail() {
    persistSidebarCollapsed(!sidebarCollapsed.value);
    if (sidebarCollapsed.value) {
        mobileNavOpen.value = false;
    }
}

function toggleDepartmentsSection() {
    if (sidebarCollapsed.value) {
        persistSidebarCollapsed(false);
        departmentsOpen.value = true;
        return;
    }

    departmentsOpen.value = !departmentsOpen.value;
}

function toggleAgenciesSection() {
    if (sidebarCollapsed.value) {
        persistSidebarCollapsed(false);
        agenciesOpen.value = true;
        return;
    }

    agenciesOpen.value = !agenciesOpen.value;
}

function itemInitial(label) {
    const text = String(label ?? '').trim();
    return text ? text.charAt(0).toUpperCase() : '?';
}

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
.sidebar-logo {
    height: 2.75rem;
    width: auto;
    max-width: calc(100% - 2.5rem);
}

.sidebar-rail-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}

.sidebar-rail-btn:hover {
    border-color: #c00000;
    background: #fef2f2;
    color: #c00000;
}

.sidebar-open-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: 1px solid #c00000;
    border-radius: 0.55rem;
    background: #c00000;
    padding: 0.45rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #ffffff;
    cursor: pointer;
    transition: background-color 0.15s;
}

.sidebar-open-btn:hover {
    background: #9f0000;
}

.nav-ico {
    display: none;
    flex-shrink: 0;
    width: 1.25rem;
    text-align: center;
    font-size: 0.85rem;
    line-height: 1;
}

.nav-ico-letter {
    font-size: 0.72rem;
    font-weight: 700;
}

.nav-link,
.nav-sublink,
.nav-group-toggle,
.sidebar-logout {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.nav-link {
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
    justify-content: center;
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
    width: 100%;
    justify-content: space-between;
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
    margin-left: auto;
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

.sidebar-logout {
    width: 100%;
    justify-content: center;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #334155;
    cursor: pointer;
    transition: background-color 0.15s;
}

.sidebar-logout:hover {
    background: #f1f5f9;
}

@media (min-width: 1024px) {
    .sidebar--collapsed .sidebar-logo {
        height: 1.85rem;
        max-width: 100%;
    }

    .sidebar--collapsed .sidebar-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.55rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .sidebar--collapsed .sidebar-brand > div {
        flex-direction: column;
        width: 100%;
        align-items: center;
    }

    .sidebar--collapsed .sidebar-rail-btn {
        margin-left: 0;
    }

    .sidebar--collapsed .nav-label {
        display: none;
    }

    .sidebar--collapsed .nav-ico {
        display: inline-block;
    }

    .sidebar--collapsed .nav-group-label {
        display: none;
    }

    .sidebar--collapsed .nav-group-children {
        padding-left: 0;
    }

    .sidebar--collapsed .nav-group-children--entities {
        display: none !important;
    }

    .sidebar--collapsed .nav-link,
    .sidebar--collapsed .nav-sublink,
    .sidebar--collapsed .nav-group-toggle,
    .sidebar--collapsed .sidebar-logout {
        justify-content: center;
        padding-left: 0.4rem;
        padding-right: 0.4rem;
    }

    .sidebar--collapsed .nav-cartographie {
        letter-spacing: 0;
        font-size: 0.7rem;
        padding: 0.55rem 0.35rem;
    }

    .sidebar--collapsed .nav-cartographie .nav-ico {
        color: #ffffff;
    }

    .sidebar--collapsed .sidebar-footer {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}
</style>
