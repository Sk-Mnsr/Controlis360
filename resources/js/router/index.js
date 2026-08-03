import { createRouter, createWebHistory } from 'vue-router';
import { canAccessModule, canCreateMission } from '../config/module-access';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/change-password',
        name: 'change-password',
        component: () => import('../views/ChangePasswordView.vue'),
        meta: { requiresAuth: true, allowPasswordChange: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: { name: 'portal' },
            },
            {
                path: 'portal',
                name: 'portal',
                component: () => import('../views/PortalView.vue'),
                meta: { isPortal: true },
            },
            {
                path: 'environments',
                name: 'environments',
                component: () => import('../views/environments/EnvironmentWorkspaceView.vue'),
                meta: { requiresEnvironmentManagement: true },
            },
            {
                path: 'environments/create',
                name: 'environments.create',
                component: () => import('../views/environments/EnvironmentFormView.vue'),
                meta: { superAdminOnly: true },
            },
            {
                path: 'environments/:id/edit',
                name: 'environments.edit',
                component: () => import('../views/environments/EnvironmentFormView.vue'),
                meta: { superAdminOnly: true, requiresEnvironmentAccess: true },
            },
            {
                path: 'environments/:id',
                name: 'environments.detail',
                component: () => import('../views/environments/EnvironmentWorkspaceView.vue'),
                meta: { requiresEnvironmentAccess: true },
            },
            {
                path: 'users',
                component: () => import('../views/users/UsersView.vue'),
                meta: { canManageUsers: true },
                children: [
                    {
                        path: '',
                        redirect: { name: 'users.create' },
                    },
                    {
                        path: 'nouveau',
                        name: 'users.create',
                        component: () => import('../views/users/UserCreatePanel.vue'),
                    },
                    {
                        path: 'historiques',
                        name: 'users.history',
                        component: () => import('../views/users/UserHistoryPanel.vue'),
                    },
                    {
                        path: ':id/edit',
                        name: 'users.edit',
                        component: () => import('../views/users/UserEditPanel.vue'),
                    },
                ],
            },
            {
                path: 'cartographie',
                meta: { module: 'cartographie' },
                children: [
                    {
                        path: '',
                        redirect: { name: 'cartographie.home' },
                    },
                    {
                        path: 'home',
                        name: 'cartographie.home',
                        component: () => import('../views/HomeView.vue'),
                    },
                    {
                        path: 'cartographie',
                        name: 'cartographie.cartographie',
                        component: () => import('../views/cartographie/CartographieView.vue'),
                    },
                    {
                        path: 'methodology/:slug',
                        name: 'cartographie.methodology.show',
                        component: () => import('../views/methodology/MethodologyPageView.vue'),
                        beforeEnter: (to) => {
                            if (to.params.slug === 'principes') {
                                return { name: 'cartographie.principes' };
                            }
                            if (to.params.slug === 'definitions-objectifs') {
                                return { name: 'cartographie.definitions-objectifs' };
                            }
                            if (to.params.slug === 'preambule') {
                                return { name: 'cartographie.preambule' };
                            }
                        },
                    },
                    {
                        path: 'dashboard',
                        name: 'cartographie.dashboard',
                        component: () => import('../views/DashboardView.vue'),
                    },
                    {
                        path: 'referentials',
                        name: 'cartographie.referentials',
                        component: () => import('../views/ReferentialsView.vue'),
                    },
                    {
                        path: 'echelle-pg',
                        name: 'cartographie.echelle-pg',
                        component: () => import('../views/cartographie/EchellePgView.vue'),
                    },
                    {
                        path: 'echelle-controle',
                        name: 'cartographie.echelle-controle',
                        component: () => import('../views/cartographie/EchelleControleView.vue'),
                    },
                    {
                        path: 'matrice-risques',
                        name: 'cartographie.matrice-risques',
                        component: () => import('../views/cartographie/MatriceRisquesView.vue'),
                    },
                    {
                        path: 'principes',
                        name: 'cartographie.principes',
                        component: () => import('../views/cartographie/PrincipesView.vue'),
                    },
                    {
                        path: 'definitions-objectifs',
                        name: 'cartographie.definitions-objectifs',
                        component: () => import('../views/cartographie/DefinitionsObjectifsView.vue'),
                    },
                    {
                        path: 'preambule',
                        name: 'cartographie.preambule',
                        component: () => import('../views/cartographie/PreambuleView.vue'),
                    },
                    {
                        path: 'lexique',
                        name: 'cartographie.lexique',
                        component: () => import('../views/cartographie/LexiqueView.vue'),
                    },
                    {
                        path: 'plus-gros-risques',
                        name: 'cartographie.plus-gros-risques',
                        component: () => import('../views/cartographie/PlusGrosRisquesView.vue'),
                    },
                    {
                        path: 'saisie-risques',
                        name: 'cartographie.saisie-risques',
                        component: () => import('../views/cartographie/SaisieRisquesView.vue'),
                        meta: { canCreateRiskRow: true },
                    },
                    {
                        path: 'departements/:code',
                        name: 'cartographie.departement-analyse',
                        component: () => import('../views/cartographie/DepartementAnalyseView.vue'),
                    },
                    {
                        path: 'departements/:code/dashboard',
                        name: 'cartographie.departement-dashboard',
                        component: () => import('../views/cartographie/DepartementDashboardView.vue'),
                    },
                    {
                        path: 'departements/:code/historique',
                        name: 'cartographie.departement-historique',
                        component: () => import('../views/cartographie/HistoriqueView.vue'),
                    },
                ],
            },
            {
                path: 'suivi-reco',
                meta: { module: 'audit' },
                children: [
                    {
                        path: '',
                        redirect: { name: 'audit.dashboard' },
                    },
                    {
                        path: 'home',
                        name: 'audit.home',
                        redirect: { name: 'audit.dashboard' },
                    },
                    {
                        path: 'dashboard',
                        name: 'audit.dashboard',
                        component: () => import('../views/audit/AuditDashboardView.vue'),
                    },
                    {
                        path: 'missions',
                        name: 'audit.missions',
                        component: () => import('../views/audit/MissionsView.vue'),
                    },
                    {
                        path: 'missions/historique',
                        name: 'audit.missions.history',
                        component: () => import('../views/audit/MissionTypeSelectView.vue'),
                    },
                    {
                        path: 'missions/historique/:missionType',
                        name: 'audit.missions.history.byType',
                        component: () => import('../views/audit/MissionHistoryView.vue'),
                    },
                    {
                        path: 'missions/:id/recommandation/nouvelle',
                        name: 'audit.missions.recommendation.create',
                        component: () => import('../views/audit/MissionRecommendationCreateView.vue'),
                    },
                    {
                        path: 'missions/:id/recommandations/:recoId/modifier',
                        name: 'audit.missions.recommendation.edit',
                        component: () => import('../views/audit/MissionRecommendationEditView.vue'),
                    },
                    {
                        path: 'missions/:id/recommandations/:recoId',
                        name: 'audit.missions.recommendation.show',
                        component: () => import('../views/audit/MissionRecommendationDetailView.vue'),
                    },
                    {
                        path: 'missions/:id',
                        name: 'audit.missions.show',
                        component: () => import('../views/audit/MissionDetailView.vue'),
                    },
                    {
                        path: 'missions/nouvelle',
                        name: 'audit.missions.create',
                        component: () => import('../views/audit/MissionCreateView.vue'),
                        meta: { canCreateMission: true },
                    },
                    {
                        path: 'missions/:id/modifier',
                        name: 'audit.missions.edit',
                        component: () => import('../views/audit/MissionEditView.vue'),
                        meta: { canCreateMission: true },
                    },
                    {
                        path: 'parametrage',
                        name: 'audit.parametrage',
                        component: () => import('../views/audit/MissionParametrageView.vue'),
                        meta: { canCreateMission: true },
                    },
                    {
                        path: 'parametrage/types-mission',
                        redirect: { name: 'audit.parametrage' },
                    },
                    {
                        path: 'regulateur',
                        name: 'audit.regulator',
                        component: () => import('../views/audit/RegulatorQueueView.vue'),
                    },
                    {
                        path: 'regulateur/recommandations/:recoId',
                        name: 'audit.regulator.show',
                        component: () => import('../views/audit/RegulatorDetailView.vue'),
                    },
                ],
            },
            {
                path: 'conformite',
                meta: { module: 'conformite' },
                children: [
                    {
                        path: '',
                        redirect: { name: 'conformite.home' },
                    },
                    {
                        path: 'home',
                        name: 'conformite.home',
                        component: () => import('../views/conformite/ConformiteHomeView.vue'),
                    },
                    {
                        path: 'reporting/nouvelle',
                        name: 'conformite.reporting.create',
                        component: () => import('../views/conformite/ReportingFicheFormView.vue'),
                    },
                    {
                        path: 'reporting/:id/modifier',
                        name: 'conformite.reporting.edit',
                        component: () => import('../views/conformite/ReportingFicheFormView.vue'),
                    },
                    {
                        path: 'reporting',
                        name: 'conformite.reporting.history',
                        component: () => import('../views/conformite/ReportingFicheHistoryView.vue'),
                    },
                    {
                        path: 'reception',
                        name: 'conformite.reporting.reception',
                        component: () => import('../views/conformite/ReportingReceptionHistoryView.vue'),
                    },
                    {
                        path: 'reception/:id',
                        name: 'conformite.reporting.reception.show',
                        component: () => import('../views/conformite/ReportingReceptionDetailView.vue'),
                    },
                ],
            },
            {
                path: 'gouvernance-it',
                meta: { module: 'gouvernance-it' },
                children: [
                    {
                        path: '',
                        redirect: { name: 'gouvernance-it.home' },
                    },
                    {
                        path: 'home',
                        name: 'gouvernance-it.home',
                        component: () => import('../views/gouvernance-it/GouvernanceItHomeView.vue'),
                    },
                    {
                        path: 'govstrat-itr',
                        name: 'gouvernance-it.govstrat-itr',
                        component: () => import('../views/gouvernance-it/GovStratItrView.vue'),
                    },
                    {
                        path: 'task-activity',
                        name: 'gouvernance-it.task-activity',
                        component: () => import('../views/gouvernance-it/TaskActivityItView.vue'),
                    },
                    {
                        path: 'centre-support',
                        name: 'gouvernance-it.centre-support',
                        component: () => import('../views/gouvernance-it/GovItOperationsSectionView.vue'),
                        props: { title: 'CENTRE SUPPORT', moduleSlug: 'centre_support' },
                    },
                    {
                        path: 'systemes-reseaux',
                        name: 'gouvernance-it.systemes-reseaux',
                        component: () => import('../views/gouvernance-it/GovItOperationsSectionView.vue'),
                        props: { title: 'SYSTEMES ET RESEAUX', moduleSlug: 'systemes_reseaux' },
                    },
                    {
                        path: 'base-donnees',
                        name: 'gouvernance-it.base-donnees',
                        component: () => import('../views/gouvernance-it/GovItOperationsSectionView.vue'),
                        props: { title: 'BASE DE DONNEES', moduleSlug: 'base_donnees' },
                    },
                ],
            },
            {
                path: 'home',
                redirect: { name: 'cartographie.home' },
            },
            {
                path: 'methodology/:slug',
                redirect: (to) => ({
                    name: 'cartographie.methodology.show',
                    params: { slug: to.params.slug },
                }),
            },
            {
                path: 'referentials',
                redirect: { name: 'cartographie.referentials' },
            },
            {
                path: 'dashboard',
                redirect: { name: 'cartographie.dashboard' },
            },
        ],
    },
];

import { canCreateOperationalRiskRow } from '../utils/cartographiePermissions';

const router = createRouter({
    history: createWebHistory(),
    routes,
});

function platformUser(auth) {
    return auth.baseUser ?? auth.user;
}

function platformProfile(auth) {
    return platformUser(auth)?.profile ?? null;
}

function canAccessEnvironment(auth, environmentId) {
    const profile = platformProfile(auth);
    if (profile === 'super_admin') return true;
    if (profile === 'admin') {
        return getAdminEnvironmentIds(auth).map(String).includes(String(environmentId));
    }
    return false;
}

function getAdminEnvironmentIds(auth) {
    const user = platformUser(auth);
    if (Array.isArray(user?.environment_ids) && user.environment_ids.length) {
        return user.environment_ids;
    }

    const environments = user?.environments ?? [];
    return environments.map((environment) => environment.id).filter((id) => id != null);
}

function canManageUsers(profile) {
    return profile === 'super_admin' || profile === 'admin';
}

function isPlatformAdminRoute(to) {
    return to.path.startsWith('/users') || to.path.startsWith('/environments');
}

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (auth.token && !auth.baseUser && !auth.user) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.token) {
        return next({ name: 'login' });
    }

    if (to.meta.guest && auth.token) {
        if (auth.mustChangePassword) {
            return next({ name: 'change-password' });
        }

        return next({ name: 'portal' });
    }

    if (auth.token && auth.mustChangePassword && !to.meta.allowPasswordChange) {
        return next({ name: 'change-password' });
    }

    const profile = platformProfile(auth);

    if (to.matched.some((record) => record.meta.canManageUsers)) {
        if (!canManageUsers(profile)) {
            return next({ name: 'portal' });
        }
    }

    const moduleSlug = to.matched.find((record) => record.meta.module)?.meta.module;
    if (moduleSlug) {
        auth.setActiveModule(moduleSlug);
    } else if (to.name === 'portal' || to.name === 'login' || isPlatformAdminRoute(to)) {
        auth.setActiveModule(null);
    }

    if (moduleSlug && auth.baseUser && !canAccessModule(auth.baseUser.profile, moduleSlug, auth.baseUser)) {
        return next({ name: 'portal' });
    }

    if (to.meta.requiresEnvironmentManagement) {
        if (profile === 'super_admin') {
            return next();
        }

        if (profile === 'admin') {
            const adminEnvironmentIds = getAdminEnvironmentIds(auth);
            if (!adminEnvironmentIds.length) {
                return next({ name: 'portal' });
            }

            if (to.name === 'environments' && adminEnvironmentIds.length === 1) {
                return next({
                    name: 'environments.detail',
                    params: { id: adminEnvironmentIds[0] },
                });
            }

            return next();
        }

        return next({ name: 'portal' });
    }

    if (to.meta.superAdminOnly && profile !== 'super_admin') {
        const adminEnvironmentIds = getAdminEnvironmentIds(auth);
        if (profile === 'admin' && adminEnvironmentIds.length === 1) {
            return next({ name: 'environments.detail', params: { id: adminEnvironmentIds[0] } });
        }
        if (profile === 'admin' && adminEnvironmentIds.length > 1 && to.name === 'environments') {
            return next();
        }
        return next({ name: 'portal' });
    }

    if (to.meta.requiresEnvironmentAccess && to.params.id) {
        if (!canAccessEnvironment(auth, to.params.id)) {
            return next({ name: 'portal' });
        }
    }

    return next();
});

export default router;
