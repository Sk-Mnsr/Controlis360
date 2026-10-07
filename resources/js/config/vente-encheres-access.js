import { profileForModule } from './module-access';

const ENCHERES_ROLES = ['client', 'comite', 'admin_encheres'];

/** Espaces visibles selon le rôle du module (cumulatifs). */
const SPACES_BY_ROLE = {
    client: ['client'],
    comite: ['client', 'comite'],
    admin_encheres: ['client', 'comite', 'admin'],
};

function explicitModuleProfile(user) {
    return user?.module_profiles?.['vente-encheres']?.profile
        ?? profileForModule(user, 'vente-encheres')?.profile
        ?? null;
}

function isPlatformAdmin(user) {
    return user?.profile === 'super_admin' || user?.profile === 'admin';
}

/** Rôle Vente aux enchères, indépendant des autres modules. */
export function venteEncheresRole(user) {
    if (!user) {
        return null;
    }

    if (isPlatformAdmin(user)) {
        return 'admin_encheres';
    }

    const assigned = user?.module_profiles?.['vente-encheres']?.profile;
    if (ENCHERES_ROLES.includes(assigned)) {
        return assigned;
    }

    if (ENCHERES_ROLES.includes(user.profile)) {
        return user.profile;
    }

    const fallback = explicitModuleProfile(user);
    return ENCHERES_ROLES.includes(fallback) ? fallback : null;
}

function canSpace(user, space) {
    const role = venteEncheresRole(user);
    return Boolean(role && SPACES_BY_ROLE[role]?.includes(space));
}

export function canAccessVenteEncheresClient(user) {
    return canSpace(user, 'client');
}

export function canAccessVenteEncheresComite(user) {
    return canSpace(user, 'comite');
}

export function canAccessVenteEncheresAdmin(user) {
    return canSpace(user, 'admin');
}
