export function buildMatrixWithEntities(matrix, entities = []) {
    const cellMap = new Map();

    for (const entity of entities) {
        const key = `${entity.cell_gravity}-${entity.cell_probability}`;

        if (!cellMap.has(key)) {
            cellMap.set(key, []);
        }

        cellMap.get(key).push(entity.name);
    }

    return matrix.map((row) => row.map((cell) => ({
        ...cell,
        entities: cellMap.get(`${cell.gravity}-${cell.probability}`) ?? [],
    })));
}

export function enrichDistribution(distribution, classifications = []) {
    return distribution.map((item) => {
        const classification = classifications.find((entry) => entry.code === item.code);

        return {
            ...item,
            name: classification?.name ?? item.code,
            color: classification?.color ?? '#94a3b8',
        };
    });
}

export function formatDashboardDate(date = new Date()) {
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export function uniqueEnvironments(entities = []) {
    const environments = new Map();

    for (const entity of entities) {
        if (entity.environment?.code) {
            environments.set(entity.environment.code, entity.environment);
        }
    }

    return [...environments.values()];
}
