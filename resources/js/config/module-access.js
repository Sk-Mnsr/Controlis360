import { modules } from './modules';

export const PROFILE_LABELS = {
    super_admin: 'Super administrateur',
    admin: 'Administrateur',
    superviseur: 'Superviseur',
    regulateur: 'Régulateur',
    controle: 'Contrôle',
    audit: 'Audit',
    conformite: 'Conformité',
    metier: 'Métier',
};

/** Profils proposés pour chaque module (hors super_admin plateforme). */
export const MODULE_PROFILE_OPTIONS = {
    cartographie: [
        { value: 'admin', label: PROFILE_LABELS.admin },
        { value: 'superviseur', label: PROFILE_LABELS.superviseur },
        { value: 'controle', label: PROFILE_LABELS.controle },
        { value: 'metier', label: PROFILE_LABELS.metier },
    ],
    audit: [
        { value: 'regulateur', label: PROFILE_LABELS.regulateur },
        { value: 'controle', label: PROFILE_LABELS.controle },
        { value: 'audit', label: PROFILE_LABELS.audit },
        { value: 'metier', label: PROFILE_LABELS.metier },
    ],
    conformite: [
        { value: 'conformite', label: PROFILE_LABELS.conformite },
        { value: 'metier', label: PROFILE_LABELS.metier },
    ],
};

const MODULE_PROFILES = {
    cartographie: ['super_admin', 'admin', 'superviseur', 'controle', 'metier'],
    audit: ['super_admin', 'admin', 'regulateur', 'controle', 'audit', 'metier'],
    conformite: ['super_admin', 'admin', 'conformite', 'metier'],
    'gouvernance-it': ['super_admin', 'admin', 'agent_it', 'responsable_it', 'responsable_regional'],
};

const ALL_MODULE_SLUGS = modules.map((module) => module.slug);

export function emptyModuleAssignment(slug) {
    const defaultProfile = MODULE_PROFILE_OPTIONS[slug]?.[0]?.value ?? 'metier';

    return {
        slug,
        enabled: false,
        profile: defaultProfile,
        controle_role: 'agent_controle_interne',
        audit_role: 'agent_audit',
        metier_role: 'visiteur',
    };
}

export function defaultModuleAssignments(user = {}) {
    const fromProfiles = user?.module_profiles && typeof user.module_profiles === 'object'
        ? user.module_profiles
        : null;

    return ALL_MODULE_SLUGS.map((slug) => {
        const base = emptyModuleAssignment(slug);
        const saved = fromProfiles?.[slug];

        if (saved && typeof saved === 'object') {
            return {
                ...base,
                enabled: true,
                profile: saved.profile ?? base.profile,
                controle_role: saved.controle_role ?? base.controle_role,
                audit_role: saved.audit_role ?? base.audit_role,
                metier_role: saved.metier_role ?? base.metier_role,
            };
        }

        if (Array.isArray(user?.modules) && user.modules.includes(slug)) {
            return {
                ...base,
                enabled: true,
                profile: user.profile && MODULE_PROFILES[slug]?.includes(user.profile)
                    ? user.profile
                    : base.profile,
                controle_role: user.controle_role ?? base.controle_role,
                audit_role: user.audit_role ?? base.audit_role,
                metier_role: user.metier_role ?? base.metier_role,
            };
        }

        if (user?.profile && canAccessModuleByProfile(user.profile, slug, user) && !fromProfiles && !user?.modules?.length) {
            return {
                ...base,
                enabled: true,
                profile: user.profile === 'super_admin' ? (MODULE_PROFILE_OPTIONS[slug]?.[0]?.value ?? 'metier') : user.profile,
                controle_role: user.controle_role ?? base.controle_role,
                audit_role: user.audit_role ?? base.audit_role,
                metier_role: user.metier_role ?? base.metier_role,
            };
        }

        return base;
    });
}

export function assignmentsToPayload(assignments = []) {
    const moduleProfiles = {};
    const moduleSlugs = [];

    for (const item of assignments) {
        if (!item?.enabled || !item.slug) {
            continue;
        }

        moduleSlugs.push(item.slug);
        moduleProfiles[item.slug] = {
            profile: item.profile,
            controle_role: item.profile === 'controle' ? item.controle_role : null,
            audit_role: item.profile === 'audit' ? item.audit_role : null,
            metier_role: item.profile === 'metier' ? item.metier_role : null,
        };
    }

    return { modules: moduleSlugs, module_profiles: moduleProfiles };
}

export const GOUVERNANCE_IT_PROFILES = ['agent_it', 'responsable_it', 'responsable_regional'];

export function primaryProfileFromAssignments(assignments = [], platformProfile = null) {
    if (platformProfile === 'super_admin' || platformProfile === 'admin' || GOUVERNANCE_IT_PROFILES.includes(platformProfile)) {
        return {
            profile: platformProfile,
            controle_role: null,
            audit_role: null,
            metier_role: null,
        };
    }

    const enabled = assignments.filter((item) => item.enabled);
    const first = enabled[0];

    if (!first) {
        return {
            profile: 'metier',
            controle_role: null,
            audit_role: null,
            metier_role: 'visiteur',
        };
    }

    return {
        profile: first.profile,
        controle_role: first.profile === 'controle' ? first.controle_role : null,
        audit_role: first.profile === 'audit' ? first.audit_role : null,
        metier_role: first.profile === 'metier' ? first.metier_role : null,
    };
}

function canAccessModuleByProfile(profile, slug, user = null) {
    if (!profile) {
        return false;
    }

    if (profile === 'super_admin') {
        return true;
    }

    if (slug === 'audit') {
        if (MODULE_PROFILES.audit.includes(profile) && profile !== 'metier') {
            return true;
        }

        if (profile === 'metier' && ['responsable_entite', 'agent'].includes(user?.metier_role)) {
            return true;
        }

        return false;
    }

    if (slug === 'conformite') {
        if (MODULE_PROFILES.conformite.includes(profile) && profile !== 'metier') {
            return true;
        }

        if (profile === 'metier' && user?.metier_role === 'responsable_entite') {
            return true;
        }

        return false;
    }

    const allowedProfiles = MODULE_PROFILES[slug];
    return allowedProfiles ? allowedProfiles.includes(profile) : false;
}

export function profileForModule(user, slug) {
    const assignment = user?.module_profiles?.[slug];
    if (assignment?.profile) {
        return assignment;
    }

    return {
        profile: user?.profile ?? null,
        controle_role: user?.controle_role ?? null,
        audit_role: user?.audit_role ?? null,
        metier_role: user?.metier_role ?? null,
    };
}

export function userWithModuleContext(user, slug) {
    if (!user || !slug) {
        return user;
    }

    const assignment = profileForModule(user, slug);
    if (!assignment?.profile) {
        return user;
    }

    return {
        ...user,
        profile: assignment.profile,
        profile_fr: PROFILE_LABELS[assignment.profile] ?? assignment.profile,
        controle_role: assignment.controle_role ?? null,
        audit_role: assignment.audit_role ?? null,
        metier_role: assignment.metier_role ?? null,
    };
}

export function canAccessModule(profile, slug, user = null) {
    if (profile === 'super_admin' || profile === 'admin') {
        return canAccessModuleByProfile(profile, slug, user);
    }

    if (user?.module_profiles && Object.keys(user.module_profiles).length > 0) {
        const assignment = user.module_profiles[slug];
        if (!assignment?.profile) {
            return false;
        }

        return canAccessModuleByProfile(assignment.profile, slug, assignment);
    }

    if (!canAccessModuleByProfile(profile, slug, user)) {
        return false;
    }

    const assigned = user?.modules;
    if (!Array.isArray(assigned) || assigned.length === 0) {
        return true;
    }

    return assigned.includes(slug);
}

export function canCreateMission(user) {
    const profile = user?.profile;

    return profile === 'super_admin'
        || profile === 'admin'
        || profile === 'controle'
        || profile === 'audit';
}

export function isPlatformAdministrator(user) {
    const profile = user?.profile;

    return profile === 'super_admin' || profile === 'admin';
}

export function isMissionResponsible(user) {
    return user?.profile === 'metier' && user?.metier_role === 'responsable_entite';
}

export function isMissionAgent(user) {
    return user?.profile === 'metier' && user?.metier_role === 'agent';
}

export function getAccessibleModules(user) {
    return modules.filter((module) => {
        if (!module.active) {
            return false;
        }

        return canAccessModule(user?.profile, module.slug, user);
    });
}

export function isAuditProfile(profile) {
    return profile === 'audit' || profile === 'controle';
}

export function isRegulatorProfile(profile) {
    return profile === 'regulateur' || profile === 'super_admin';
}
