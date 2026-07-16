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

function averageNumeric(values) {
    const numbers = values.filter((value) => value !== null && value !== undefined && value !== '');

    if (!numbers.length) {
        return null;
    }

    return numbers.reduce((sum, value) => sum + Number(value), 0) / numbers.length;
}

function roundScore(value) {
    if (value === null || value === undefined) {
        return null;
    }

    return Math.round(Number(value) * 10) / 10;
}

export function classificationForScore(score, classifications = []) {
    if (score === null || score === undefined || score === '') {
        return null;
    }

    const rounded = Math.round(Number(score));

    if (rounded <= 0) {
        return null;
    }

    return classifications.find(
        (item) => rounded >= item.min_score && rounded <= item.max_score,
    ) ?? null;
}

function categoryShortLabel(name) {
    const labels = {
        'Risques opérationnels': 'Risque opérationnel',
        'Risques de fraude': 'Risque de Fraude',
        'Risques de crédit': 'Risque de crédit',
        'Risques stratégiques': 'Risque stratégique',
        'Risques de liquidité': 'Risque de liquidité',
        'Risques réglementaires': 'Risque réglementaire',
        'Risques exogènes ou externes': 'Risque exogène',
    };

    return labels[name] ?? name;
}

function buildFamilyCategoryMap(categories = []) {
    const map = new Map();

    for (const category of categories) {
        for (const family of category.families ?? []) {
            map.set(family.name, category);
        }
    }

    return map;
}

function trendForScore(score, maxScore) {
    if (!score || score <= 0) {
        return '—';
    }

    if (score === maxScore) {
        return '↗';
    }

    return '→';
}

export function computeRiskCategorySummary(rows, categories = [], classifications = [], mode = 'gross') {
    const familyMap = buildFamilyCategoryMap(categories);
    const grouped = new Map();

    for (const category of categories) {
        grouped.set(category.id, []);
    }

    for (const row of rows ?? []) {
        const category = familyMap.get(row.risk_family);

        if (!category) {
            continue;
        }

        grouped.get(category.id)?.push(row);
    }

    const items = categories.map((category) => {
        const categoryRows = grouped.get(category.id) ?? [];
        const occurrence = categoryRows.length;
        const averages = computeRiskAverages(categoryRows);
        const evaluationScore = mode === 'residual'
            ? (averages.residual.risk ?? 0)
            : (averages.gross.risk ?? 0);

        return {
            id: category.id,
            label: categoryShortLabel(category.name),
            score: occurrence,
            evaluationScore,
            classification: evaluationScore > 0
                ? classificationForScore(evaluationScore, classifications)
                : null,
            trend: null,
        };
    });

    const maxOccurrence = items.reduce((max, item) => Math.max(max, item.score ?? 0), 0);

    return items.map((item) => ({
        ...item,
        trend: trendForScore(item.score, maxOccurrence),
        levelLabel: item.evaluationScore > 0
            ? (item.classification?.name ?? '—')
            : 'Très faible / Nul',
    }));
}

export function computeRiskAverages(rows) {
    const gravities = [];
    const probabilities = [];
    const residualGravities = [];
    const residualProbabilities = [];

    for (const row of rows ?? []) {
        if (row.gravity != null && row.probability != null) {
            gravities.push(Number(row.gravity));
            probabilities.push(Number(row.probability));
        }

        const residual = resolvedResidualFields(row);

        if (residual.gravity != null && residual.probability != null) {
            residualGravities.push(Number(residual.gravity));
            residualProbabilities.push(Number(residual.probability));
        }
    }

    const avgG = roundScore(averageNumeric(gravities));
    const avgP = roundScore(averageNumeric(probabilities));
    const avgRb = avgG !== null && avgP !== null ? roundScore(avgG * avgP) : null;

    const avgResidualG = roundScore(averageNumeric(residualGravities));
    const avgPr = roundScore(averageNumeric(residualProbabilities));
    const avgRr = avgResidualG !== null && avgPr !== null ? roundScore(avgResidualG * avgPr) : null;

    return {
        gross: {
            gravity: avgG,
            probability: avgP,
            risk: avgRb,
        },
        residual: {
            gravity: avgResidualG,
            probability: avgPr,
            risk: avgRr,
        },
    };
}
