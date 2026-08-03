export function canEditMethodology(user) {
    return ['super_admin', 'admin'].includes(user?.profile)
        || (user?.profile === 'controle' && user?.controle_role === 'responsable_controle_permanent');
}

export function canCreateOperationalRiskRow(user) {
    return ['super_admin', 'admin'].includes(user?.profile)
        || (user?.profile === 'controle' && ['agent_controle_interne', 'responsable_controle_permanent'].includes(user?.controle_role));
}
