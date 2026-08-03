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

export function subProcessFieldsFromRow(row) {
    return {
        process_number: row.process_number,
        process_name: row.process_name ?? '',
        ratio: row.ratio,
        sub_process_name: row.sub_process_name ?? '',
    };
}
