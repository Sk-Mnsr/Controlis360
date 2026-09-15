import { profileForModule } from '../config/module-access';

export function canEditMethodology(user) {
    if (['super_admin', 'admin'].includes(user?.profile)) {
        return true;
    }

    const assignment = profileForModule(user, 'cartographie');

    return assignment?.profile === 'controle'
        && assignment?.controle_role === 'responsable_controle_permanent';
}

export function canCreateOperationalRiskRow(user) {
    if (['super_admin', 'admin'].includes(user?.profile)) {
        return true;
    }

    const assignment = profileForModule(user, 'cartographie');

    return assignment?.profile === 'controle'
        && ['agent_controle_interne', 'responsable_controle_permanent'].includes(assignment?.controle_role);
}
