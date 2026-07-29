export function subProcessKey(row) {
    return [
        row.process_number ?? '',
        row.process_name ?? '',
        row.ratio ?? '',
        row.sub_process_name ?? '',
    ].join('::');
}

export function groupRowsBySubProcess(rows) {
    const groups = [];
    const indexByKey = new Map();

    for (const row of rows) {
        const key = subProcessKey(row);

        if (!indexByKey.has(key)) {
            const group = {
                key,
                process_number: row.process_number,
                process_name: row.process_name,
                ratio: row.ratio,
                sub_process_name: row.sub_process_name,
                exceptions: [],
            };
            indexByKey.set(key, groups.length);
            groups.push(group);
        }

        groups[indexByKey.get(key)].exceptions.push(row);
    }

    return assignDisplayProcessNumbers(groups);
}

/**
 * Complète les N° manquants pour une numérotation continue (1, 2, 3…).
 */
export function assignDisplayProcessNumbers(groups) {
    const used = new Set(
        groups
            .map((group) => group.process_number)
            .filter((value) => value !== null && value !== undefined && value !== '')
            .map(Number),
    );

    let next = 1;

    return groups.map((group) => {
        const hasNumber = group.process_number !== null
            && group.process_number !== undefined
            && group.process_number !== '';

        if (hasNumber) {
            return {
                ...group,
                display_number: Number(group.process_number),
            };
        }

        while (used.has(next)) {
            next += 1;
        }

        const displayNumber = next;
        used.add(displayNumber);
        next += 1;

        return {
            ...group,
            display_number: displayNumber,
        };
    });
}

export function nextProcessNumber(groups) {
    const numbers = groups
        .map((group) => group.process_number ?? group.display_number)
        .filter((value) => value !== null && value !== undefined && value !== '')
        .map(Number);

    return (numbers.length ? Math.max(...numbers) : 0) + 1;
}

export function groupRowsByProcess(rows) {
    const groups = [];
    const indexByKey = new Map();

    for (const row of rows) {
        const processName = row.process_name || row.entity?.name || '—';

        if (!indexByKey.has(processName)) {
            const group = {
                process_name: processName,
                rows: [],
            };
            indexByKey.set(processName, groups.length);
            groups.push(group);
        }

        groups[indexByKey.get(processName)].rows.push(row);
    }

    return groups;
}

export function subProcessFieldsFromRow(row) {
    return {
        process_number: row.process_number,
        process_name: row.process_name ?? '',
        ratio: row.ratio,
        sub_process_name: row.sub_process_name ?? '',
    };
}
