import { modules } from './modules';

const MODULE_PROFILES = {
    cartographie: ['super_admin', 'admin', 'superviseur', 'controle', 'metier'],
    audit: ['super_admin', 'regulateur', 'controle', 'audit'],
};

export function canAccessModule(profile, slug, user = null) {
    if (!profile) {
        return false;
    }

    if (slug === 'audit') {
        if (MODULE_PROFILES.audit.includes(profile)) {
            return true;
        }

        if (profile === 'metier' && user?.metier_role === 'responsable_entite') {
            return true;
        }

        if (profile === 'metier' && user?.metier_role === 'agent') {
            return true;
        }

        return false;
    }

    const allowedProfiles = MODULE_PROFILES[slug];
    return allowedProfiles ? allowedProfiles.includes(profile) : false;
}

export function canCreateMission(user) {
    const profile = user?.profile;

    return profile === 'super_admin' || profile === 'controle' || profile === 'audit';
}

export function isMissionResponsible(user) {
    return user?.profile === 'metier' && user?.metier_role === 'responsable_entite';
}

export function isMissionAgent(user) {
    return user?.profile === 'metier' && user?.metier_role === 'agent';
}

export function getAccessibleModules(user) {
    const profile = user?.profile;

    return modules.filter((module) => {
        if (!module.active) {
            return false;
        }

        return canAccessModule(profile, module.slug, user);
    });
}

export function isAuditProfile(profile) {
    return profile === 'audit' || profile === 'controle';
}

export function isRegulatorProfile(profile) {
    return profile === 'regulateur' || profile === 'super_admin';
}
