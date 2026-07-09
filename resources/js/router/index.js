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
                path: 'entities',
                name: 'entities.members',
                component: () => import('../views/entities/EntityMembersView.vue'),
                meta: { canManageUsers: true },
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
                        redirect: { name: 'audit.home' },
                    },
                    {
                        path: 'home',
                        name: 'audit.home',
                        component: () => import('../views/audit/AuditHomeView.vue'),
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

function canAccessEnvironment(auth, environmentId) {
    if (auth.user?.profile === 'super_admin') return true;
    if (auth.user?.profile === 'admin') {
        const environmentIds = auth.user.environment_ids ?? [];
        return environmentIds.map(String).includes(String(environmentId));
    }
    return false;
}

function getAdminEnvironmentIds(auth) {
    return auth.user?.environment_ids ?? [];
}

function canManageUsers(profile) {
    return profile === 'super_admin' || profile === 'admin';
}

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (auth.token && !auth.user) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.token) {
        return next({ name: 'login' });
    }

    if (to.meta.guest && auth.token) {
        return next({ name: 'portal' });
    }

    if (to.matched.some((record) => record.meta.canManageUsers)) {
        if (!canManageUsers(auth.user?.profile)) {
            return next({ name: 'portal' });
        }
    }

    if (to.meta.requiresEnvironmentManagement) {
        if (auth.user?.profile === 'super_admin') {
            return next();
        }
        if (auth.user?.profile === 'admin' && getAdminEnvironmentIds(auth).length) {
            return next();
        }
        return next({ name: 'portal' });
    }

    if (to.meta.superAdminOnly && auth.user?.profile !== 'super_admin') {
        const adminEnvironmentIds = getAdminEnvironmentIds(auth);
        if (auth.user?.profile === 'admin' && adminEnvironmentIds.length === 1) {
            return next({ name: 'environments.detail', params: { id: adminEnvironmentIds[0] } });
        }
        if (auth.user?.profile === 'admin' && adminEnvironmentIds.length > 1 && to.name === 'environments') {
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
