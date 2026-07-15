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

    return groups;
}

<<<<<<< HEAD
=======
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

>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
export function subProcessFieldsFromRow(row) {
    return {
        process_number: row.process_number,
        process_name: row.process_name ?? '',
        ratio: row.ratio,
        sub_process_name: row.sub_process_name ?? '',
    };
}
