const MATRIX_MAP = {
    6: ['modere', 'modere', 'eleve', 'tres_eleve', 'critique', 'critique'],
    5: ['modere', 'eleve', 'eleve', 'tres_eleve', 'critique', 'critique'],
    4: ['faible', 'modere', 'modere', 'eleve', 'tres_eleve', 'tres_eleve'],
    3: ['faible', 'faible', 'modere', 'modere', 'eleve', 'eleve'],
    2: ['faible', 'faible', 'faible', 'faible', 'modere', 'modere'],
    1: ['non_significatif', 'non_significatif', 'non_significatif', 'non_significatif', 'non_significatif', 'non_significatif'],
};

export function grossRiskScore(gravity, probability) {
    if (!gravity || !probability) {
        return null;
    }

    return gravity * probability;
}

export function residualProbability(probability, controlEffectiveness) {
    if (!probability || !controlEffectiveness) {
        return null;
    }

    const value = Math.round((probability / controlEffectiveness) * 10) / 10;

    return Math.max(1, Math.min(6, value));
}

export function residualRiskScore(gravity, probability, controlEffectiveness) {
    const residualGravity = gravity ?? null;
    const residualPr = residualProbability(probability, controlEffectiveness);

    if (!residualGravity || residualPr === null) {
        return null;
    }

    return Math.round(residualGravity * residualPr * 10) / 10;
}

export function formatRiskScore(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return null;
    }

    return Number.isInteger(number) ? String(number) : number.toFixed(1);
}

export function resolvedResidualFields(row) {
    const gravity = row?.residual_gravity ?? row?.gravity ?? null;
    const probability = row?.residual_probability
        ?? residualProbability(row?.probability, row?.control_effectiveness);
    const risk = gravity && probability !== null
        ? Math.round(gravity * probability * 10) / 10
        : row?.residual_risk ?? null;

    return { gravity, probability, risk };
}

export function classificationForCell(gravity, probability, classifications = []) {
    const code = MATRIX_MAP[probability]?.[gravity - 1];

    if (!code) {
        return null;
    }

    return classifications.find((item) => item.code === code) ?? null;
}

export function scoreStyle(classification) {
    const color = classification?.color;

    if (!color) {
        return {};
    }

    const isLight = ['#fff176', '#81c784', '#ffb74d'].includes(color);

    return {
        backgroundColor: color,
        color: isLight ? '#111111' : '#ffffff',
        fontWeight: '700',
    };
}
