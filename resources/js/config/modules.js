export const modules = [
    {
        slug: 'cartographie',
        name: 'Cartographie des risques',
        description: 'Identification, évaluation et pilotage des risques opérationnels.',
        entryRoute: 'cartographie.home',
        active: true,
        comingSoon: false,
        accent: '#c00000',
    },
    {
        slug: 'audit',
        name: 'Suivi des reco',
        description: 'Planification et suivi des recommandations et missions.',
        entryRoute: 'audit.home',
        active: true,
        comingSoon: false,
        accent: '#047857',
    },
    {
        slug: 'conformite',
        name: 'Conformité',
        description: 'Suivi réglementaire et dispositifs de conformité.',
        entryRoute: null,
        active: false,
        comingSoon: true,
        accent: '#2563eb',
    },
];

export function getModule(slug) {
    return modules.find((module) => module.slug === slug) ?? null;
}

export function getActiveModules() {
    return modules.filter((module) => module.active);
}

export function getModuleFromRoute(route) {
    const slug = route.matched.find((record) => record.meta.module)?.meta.module;
    return slug ? getModule(slug) : null;
}
