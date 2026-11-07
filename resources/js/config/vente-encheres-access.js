import { profileForModule } from './module-access';

const ENCHERES_ROLES = ['client', 'comite', 'admin_encheres'];

/** Espaces visibles selon le rôle du module (cumulatifs). */
const SPACES_BY_ROLE = {
    client: ['client'],
    comite: ['client', 'comite'],
    admin_encheres: ['client', 'comite', 'admin'],
};

function assignedProfiles(user) {
    const assignment = user?.module_profiles?.['vente-encheres'];
    const listed = Array.isArray(assignment?.profiles) ? assignment.profiles : [];
    const single = assignment?.profile ?? profileForModule(user, 'vente-encheres')?.profile;
    const own = ENCHERES_ROLES.includes(user?.profile) ? [user.profile] : [];

    return [...new Set([...listed, single, ...own].filter((role) => ENCHERES_ROLES.includes(role)))];
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

    const profiles = assignedProfiles(user);
    if (profiles.includes('admin_encheres')) {
        return 'admin_encheres';
    }
    if (profiles.includes('comite')) {
        return 'comite';
    }
    if (profiles.includes('client')) {
        return 'client';
    }

    return null;
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
