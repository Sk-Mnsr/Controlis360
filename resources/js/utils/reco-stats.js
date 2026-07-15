export function recoStatusBucket(status = 'emise') {
    if (['traitee', 'transmis', 'cloturee'].includes(status)) {
        return 'implemented';
    }

    if (status === 'en_cours') {
        return 'in_progress';
    }

    return 'no_start';
}

export function recommendationDepartments(reco) {
    if (reco.entities?.length) {
        return reco.entities;
    }

    if (reco.entity_id && reco.entity_name) {
        return [{ id: reco.entity_id, name: reco.entity_name }];
    }

    if (reco.entity_name) {
        return [{ id: reco.entity_id ?? reco.entity_name, name: reco.entity_name }];
    }

    return [];
}

export function accumulateRecommendationByDepartment(target, reco, ownerEntityIds = null) {
    const bucket = recoStatusBucket(reco.status);
    let departments = recommendationDepartments(reco);

    if (ownerEntityIds?.size) {
        departments = departments.filter((department) => ownerEntityIds.has(String(department.id)));
    }

    for (const department of departments) {
        const key = String(department.id ?? department.name);
        if (!target.has(key)) {
            target.set(key, {
                id: department.id ?? key,
                name: department.name ?? '—',
                total: 0,
                implemented: 0,
                in_progress: 0,
                no_start: 0,
            });
        }

        const row = target.get(key);
        row.total += 1;
        row[bucket] += 1;
    }
}

export function finalizeDepartmentStats(target) {
    return [...target.values()]
        .map((row) => ({
            ...row,
            implementation_rate: row.total > 0
                ? Math.round((row.implemented / row.total) * 100)
                : 0,
        }))
        .sort((a, b) => a.name.localeCompare(b.name, 'fr'));
}

export function buildDepartmentStatsFromRecommendations(recommendations, ownerEntityIds = null) {
    const target = new Map();
    const ownerIds = ownerEntityIds?.length
        ? new Set(ownerEntityIds.map((id) => String(id)))
        : null;

    for (const reco of recommendations) {
        accumulateRecommendationByDepartment(target, reco, ownerIds);
    }

    return finalizeDepartmentStats(target);
}

export function buildDepartmentStatsFromMissions(missions) {
    const target = new Map();

    for (const mission of missions) {
        const recommendations = mission.recommendations?.length
            ? mission.recommendations
            : (mission.recommendation ? [mission.recommendation] : []);

        for (const reco of recommendations) {
            accumulateRecommendationByDepartment(target, reco);
        }
    }

    return finalizeDepartmentStats(target);
}

export function recommendationsForDepartment(recommendations, departmentId) {
    return recommendations.filter((reco) => (
        recommendationDepartments(reco).some((department) => String(department.id) === String(departmentId))
    ));
}

export function findLatestRecommendation(recommendations) {
    if (!recommendations?.length) return null;

    return [...recommendations].sort((a, b) => {
        const dateCompare = String(b.recommendation_date ?? '').localeCompare(String(a.recommendation_date ?? ''));
        if (dateCompare !== 0) return dateCompare;

        return Number(b.id ?? 0) - Number(a.id ?? 0);
    })[0];
}

export function defaultDepartmentIdForRecommendations(recommendations, ownerEntityIds = null) {
    const latest = findLatestRecommendation(recommendations);
    if (!latest) return null;

    let departments = recommendationDepartments(latest);

    if (ownerEntityIds?.length) {
        const ids = new Set(ownerEntityIds.map((id) => String(id)));
        departments = departments.filter((department) => ids.has(String(department.id)));
    }

    return departments[0]?.id ?? null;
}

export function ownerDepartmentsForReco(reco, ownerEntityIds = []) {
    const ids = new Set(ownerEntityIds.map((id) => String(id)));

    return recommendationDepartments(reco).filter((department) => ids.has(String(department.id)));
}

export function ownerResponsibleLabel(reco, ownerName = '') {
    if (!ownerName) {
        return reco.responsible_name || '—';
    }

    const names = String(reco.responsible_name ?? '')
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean);

    const match = names.find((name) => name === ownerName);

    return match || ownerName;
}

export function recommendationsForOwner(recommendations, ownerEntityIds = []) {
    const ids = new Set(ownerEntityIds.map((id) => String(id)));

    if (!ids.size) {
        return [];
    }

    return recommendations.filter((reco) => (
        recommendationDepartments(reco).some((department) => ids.has(String(department.id)))
    ));
}

export function buildRecommendationStats(recommendations) {
    return recommendations
        .map((reco) => {
            const bucket = recoStatusBucket(reco.status);

            return {
                id: reco.id,
                reference: reco.reference,
                reco,
                total: 1,
                implemented: bucket === 'implemented' ? 1 : 0,
                in_progress: bucket === 'in_progress' ? 1 : 0,
                no_start: bucket === 'no_start' ? 1 : 0,
                implementation_rate: bucket === 'implemented' ? 100 : 0,
            };
        })
        .sort((a, b) => String(a.reference).localeCompare(String(b.reference), 'fr'));
}

export function buildMissionStatsFromRecommendations(recommendations) {
    const stats = {
        total: 0,
        implemented: 0,
        in_progress: 0,
        no_start: 0,
    };

    for (const reco of recommendations) {
        const bucket = recoStatusBucket(reco.status);
        stats.total += 1;
        stats[bucket] += 1;
    }

    stats.implementation_rate = stats.total > 0
        ? Math.round((stats.implemented / stats.total) * 100)
        : 0;

    return stats;
}

export function displayStatValue(value) {
    return value === undefined || value === null || value === 0 ? '' : value;
}
