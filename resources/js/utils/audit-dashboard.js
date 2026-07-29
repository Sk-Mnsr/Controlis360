import { recoStatusBucket } from './reco-stats';
import { recommendationRemainingDays } from './mission-progress';

export function flattenMissionRecommendations(missions = []) {
    return missions.flatMap((mission) => {
        const recos = mission.recommendations?.length
            ? mission.recommendations
            : (mission.recommendation ? [mission.recommendation] : []);

        return recos.map((reco) => ({
            ...reco,
            mission_id: mission.id,
            mission_reference: mission.reference,
            mission_type: mission.mission_type,
            mission_type_fr: mission.mission_type_fr,
        }));
    });
}

export function buildAuditDashboardStats(recommendations = [], resolveDeadlineStatus) {
    const total = recommendations.length;
    let implemented = 0;
    let inProgress = 0;
    let noStart = 0;
    let late = 0;
    let onTime = 0;
    let withDeadline = 0;
    let criticalOpen = 0;
    let criticalLate = 0;

    const byRisk = new Map();
    const byStatus = new Map();
    const byEntity = new Map();
    const byMissionType = new Map();
    const byMonth = new Map();

    for (const reco of recommendations) {
        const bucket = recoStatusBucket(reco.status);
        if (bucket === 'implemented') implemented += 1;
        else if (bucket === 'in_progress') inProgress += 1;
        else noStart += 1;

        const remaining = recommendationRemainingDays(reco);
        const deadline = typeof resolveDeadlineStatus === 'function'
            ? resolveDeadlineStatus(remaining)
            : { tone: 'neutral', label: '—' };

        if (remaining !== null && remaining !== undefined) {
            withDeadline += 1;
            if (remaining < 0) late += 1;
            else onTime += 1;
        }

        const riskKey = reco.risk_level || reco.risk_level_fr || 'non_renseigne';
        const riskLabel = reco.risk_level_fr || reco.risk_level || 'Non renseigné';
        if (!byRisk.has(riskKey)) {
            byRisk.set(riskKey, { code: riskKey, name: riskLabel, count: 0 });
        }
        byRisk.get(riskKey).count += 1;

        const statusKey = bucket;
        const statusLabels = {
            implemented: 'Clôturées / implémentées',
            in_progress: 'En cours',
            no_start: 'No start',
        };
        if (!byStatus.has(statusKey)) {
            byStatus.set(statusKey, { code: statusKey, name: statusLabels[statusKey], count: 0 });
        }
        byStatus.get(statusKey).count += 1;

        for (const entity of (reco.entities?.length ? reco.entities : [{ id: reco.entity_id, name: reco.entity_name }])) {
            if (!entity?.name && !entity?.id) continue;
            const key = String(entity.id ?? entity.name);
            if (!byEntity.has(key)) {
                byEntity.set(key, { id: entity.id ?? key, name: entity.name || '—', count: 0 });
            }
            byEntity.get(key).count += 1;
        }

        const typeKey = reco.mission_type || 'autre';
        const typeLabel = reco.mission_type_fr || reco.mission_type || 'Autre';
        if (!byMissionType.has(typeKey)) {
            byMissionType.set(typeKey, { code: typeKey, name: typeLabel, count: 0 });
        }
        byMissionType.get(typeKey).count += 1;

        const due = reco.due_date || null;
        if (due) {
            const monthKey = String(due).slice(0, 7);
            if (!byMonth.has(monthKey)) {
                byMonth.set(monthKey, {
                    month: monthKey,
                    total: 0,
                    implemented: 0,
                    in_progress: 0,
                    late: 0,
                });
            }
            const row = byMonth.get(monthKey);
            row.total += 1;
            if (bucket === 'implemented') row.implemented += 1;
            else if (bucket === 'in_progress') row.in_progress += 1;
            if (remaining !== null && remaining < 0 && bucket !== 'implemented') row.late += 1;
        }

        const isCritical = ['critique', 'critical', 'eleve', 'élevé', 'haut', 'high']
            .some((token) => String(riskKey).toLowerCase().includes(token)
                || String(riskLabel).toLowerCase().includes(token));

        if (isCritical && bucket !== 'implemented') {
            criticalOpen += 1;
            if (remaining !== null && remaining < 0) criticalLate += 1;
        }

        reco._deadline = deadline;
        reco._remaining = remaining;
        reco._bucket = bucket;
        reco._isCritical = isCritical;
    }

    const toDistribution = (map) => {
        const items = [...map.values()];
        const sum = items.reduce((acc, item) => acc + item.count, 0) || 1;
        return items
            .map((item) => ({
                ...item,
                percent: Math.round((item.count / sum) * 100),
            }))
            .sort((a, b) => b.count - a.count);
    };

    const alerts = recommendations
        .filter((reco) => reco._bucket !== 'implemented' && reco._remaining !== null)
        .sort((a, b) => (a._remaining ?? 9999) - (b._remaining ?? 9999))
        .slice(0, 8);

    const topCritical = recommendations
        .filter((reco) => reco._isCritical && reco._bucket !== 'implemented')
        .sort((a, b) => (a._remaining ?? 9999) - (b._remaining ?? 9999))
        .slice(0, 5);

    const monthly = [...byMonth.values()]
        .sort((a, b) => a.month.localeCompare(b.month))
        .slice(-6);

    return {
        total,
        implemented,
        in_progress: inProgress,
        no_start: noStart,
        late,
        implementation_rate: total > 0 ? Math.round((implemented / total) * 1000) / 10 : 0,
        on_time_rate: withDeadline > 0 ? Math.round((onTime / withDeadline) * 100) : 0,
        critical_open: criticalOpen,
        critical_late: criticalLate,
        by_risk: toDistribution(byRisk),
        by_status: toDistribution(byStatus),
        by_entity: [...byEntity.values()].sort((a, b) => b.count - a.count).slice(0, 8),
        by_mission_type: toDistribution(byMissionType),
        monthly,
        alerts,
        top_critical: topCritical,
    };
}
