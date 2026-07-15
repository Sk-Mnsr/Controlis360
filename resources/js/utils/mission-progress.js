function parseIsoDate(value) {
    if (!value) return null;

    const [year, month, day] = String(value).split('-').map(Number);
    if (!year || !month || !day) return null;

    return new Date(year, month - 1, day);
}

function startOfDay(date) {
    const copy = new Date(date);
    copy.setHours(0, 0, 0, 0);
    return copy;
}

export function missionImplementationRate(mission) {
    return mission.reco_stats?.implementation_rate ?? 0;
}

export function missionDeadlineDate(mission) {
    const dueDates = (mission.recommendations ?? [])
        .map((recommendation) => recommendation.due_date)
        .filter(Boolean);

    if (mission.recommendation?.due_date) {
        dueDates.push(mission.recommendation.due_date);
    }

    if (dueDates.length) {
        return [...new Set(dueDates)].sort()[0];
    }

    return mission.end_date ?? null;
}

export function remainingDaysFromDueDate(dueDate, referenceDate = new Date()) {
    const deadline = parseIsoDate(dueDate);
    if (!deadline) return null;

    const today = startOfDay(referenceDate);
    const dueDay = startOfDay(deadline);
    const diffMs = dueDay.getTime() - today.getTime();

    return Math.round(diffMs / (1000 * 60 * 60 * 24));
}

export function deadlineStatusFromRemainingDays(remainingDays, nearDeadlineThreshold = 10) {
    if (remainingDays === null) {
        return { label: '—', tone: 'neutral' };
    }

    if (remainingDays < 0) {
        return { label: 'En retard', tone: 'late' };
    }

    if (remainingDays <= nearDeadlineThreshold) {
        return { label: 'Proche échéance', tone: 'warning' };
    }

    return { label: 'Dans les délais', tone: 'ok' };
}

export function missionRemainingDays(mission, referenceDate = new Date()) {
    return remainingDaysFromDueDate(mission.end_date, referenceDate);
}

export function missionDeadlineStatus(mission, nearDeadlineThreshold = 10) {
    return deadlineStatusFromRemainingDays(
        missionRemainingDays(mission),
        nearDeadlineThreshold,
    );
}

export function recommendationRemainingDays(recommendation, referenceDate = new Date()) {
    return remainingDaysFromDueDate(recommendation?.due_date, referenceDate);
}

export function recommendationDeadlineStatus(recommendation, nearDeadlineThreshold = 10) {
    return deadlineStatusFromRemainingDays(
        recommendationRemainingDays(recommendation),
        nearDeadlineThreshold,
    );
}

export function deadlineStatusClasses(tone) {
    return {
        late: 'bg-red-500 text-white',
        warning: 'bg-orange-500 text-white',
        ok: 'bg-emerald-500 text-white',
        neutral: 'bg-slate-100 text-slate-600',
    }[tone] ?? 'bg-slate-100 text-slate-600';
}

export function remainingDaysClasses(remainingDays, nearDeadlineThreshold = 10) {
    if (remainingDays === null) return 'text-slate-500';
    if (remainingDays < 0) return 'text-red-600 font-semibold';
    if (remainingDays <= nearDeadlineThreshold) return 'text-yellow-600 font-semibold';
    return 'text-slate-800 font-medium';
}
