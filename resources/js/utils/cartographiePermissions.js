export function canEditMethodology(user) {
    return user?.profile === 'super_admin'
        || (user?.profile === 'controle' && user?.controle_role === 'responsable_controle_permanent');
}

export function canCreateOperationalRiskRow(user) {
    return user?.profile === 'super_admin'
        || (user?.profile === 'controle' && ['agent_controle_interne', 'responsable_controle_permanent'].includes(user?.controle_role));
}
