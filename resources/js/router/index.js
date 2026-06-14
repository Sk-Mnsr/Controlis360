import { createRouter, createWebHistory } from 'vue-router';
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
                redirect: { name: 'home' },
            },
            {
                path: 'home',
                name: 'home',
                component: () => import('../views/HomeView.vue'),
            },
            {
                path: 'methodology/:slug',
                name: 'methodology.show',
                component: () => import('../views/methodology/MethodologyPageView.vue'),
            },
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('../views/DashboardView.vue'),
            },
            {
                path: 'referentials',
                name: 'referentials',
                component: () => import('../views/ReferentialsView.vue'),
            },
            {
                path: 'environments',
                name: 'environments',
                component: () => import('../views/environments/EnvironmentWorkspaceView.vue'),
                meta: { superAdminOnly: true },
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
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

function canAccessEnvironment(auth, environmentId) {
    if (auth.user?.profile === 'super_admin') return true;
    if (auth.user?.profile === 'admin') {
        return String(auth.user.environment_id) === String(environmentId);
    }
    return false;
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
        return next({ name: 'home' });
    }

    if (to.matched.some((record) => record.meta.canManageUsers)) {
        if (!canManageUsers(auth.user?.profile)) {
            return next({ name: 'home' });
        }
    }

    if (to.meta.superAdminOnly && auth.user?.profile !== 'super_admin') {
        if (auth.user?.profile === 'admin' && auth.user.environment_id) {
            return next({ name: 'environments.detail', params: { id: auth.user.environment_id } });
        }
        return next({ name: 'home' });
    }

    if (to.meta.requiresEnvironmentAccess && to.params.id) {
        if (!canAccessEnvironment(auth, to.params.id)) {
            return next({ name: 'home' });
        }
    }

    return next();
});

export default router;
