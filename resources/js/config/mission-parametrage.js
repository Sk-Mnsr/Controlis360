export const MISSION_TYPES = [
    { value: 'audit_interne', label: 'Audit Interne', profiles: ['audit'] },
    { value: 'audit_externe', label: 'Audit Externe', profiles: ['audit'] },
    { value: 'controle_permanent', label: 'Contrôle Permanent', profiles: ['controle'] },
    { value: 'inspection', label: 'Inspection', profiles: ['controle'] },
    { value: 'cac', label: 'CAC', profiles: ['audit'] },
    { value: 'regulateur', label: 'Régulateur', profiles: ['audit', 'controle'] },
];

export function getMissionTypesForProfile(profile, currentValue = null) {
    const allowed = profile === 'super_admin'
        ? MISSION_TYPES
        : MISSION_TYPES.filter((type) => type.profiles.includes(profile));

    if (currentValue && !allowed.some((type) => type.value === currentValue)) {
        const current = MISSION_TYPES.find((type) => type.value === currentValue);
        if (current) {
            return [...allowed, current];
        }
    }

    return allowed;
}

export const RISK_LEVELS = [
    { value: 'faible', label: 'Faible' },
    { value: 'moyen', label: 'Moyen' },
    { value: 'eleve', label: 'Élevé' },
    { value: 'critique', label: 'Critique' },
];

export const PRIORITIES = [
    { value: 'basse', label: 'Basse' },
    { value: 'moyenne', label: 'Moyenne' },
    { value: 'haute', label: 'Haute' },
];

export const MISSION_STATUSES = [
    { value: 'ouvert', label: 'Ouvert' },
    { value: 'ferme', label: 'Fermé' },
];

export const RECOMMENDATION_STATUSES = [
    { value: 'emise', label: 'Émise' },
    { value: 'en_cours', label: 'En cours' },
    { value: 'traitee', label: 'Traitée' },
    { value: 'transmis', label: 'Transmis' },
    { value: 'cloturee', label: 'Clôturée' },
];

export const ACTION_PLAN_STATUSES = [
    { value: 'non_demarre', label: 'Non démarré', color: 'blue' },
    { value: 'en_cours', label: 'En cours', color: 'amber' },
    { value: 'en_attente', label: 'En attente', color: 'orange' },
    { value: 'en_retard', label: 'En retard', color: 'red' },
    { value: 'cloture', label: 'Clôturé', color: 'emerald' },
    { value: 'annule', label: 'Annulé', color: 'slate' },
];

export function actionPlanStatusClasses(color) {
    return {
        blue: 'bg-blue-100 text-blue-800',
        amber: 'bg-amber-100 text-amber-800',
        orange: 'bg-orange-100 text-orange-800',
        red: 'bg-red-100 text-red-800',
        emerald: 'bg-emerald-100 text-emerald-800',
        slate: 'bg-slate-200 text-slate-700',
    }[color] ?? 'bg-slate-100 text-slate-700';
}
