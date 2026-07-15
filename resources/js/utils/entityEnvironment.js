export function environmentCodeFrom(source) {
    if (!source) {
        return null;
    }

    if (typeof source === 'string') {
        return source;
    }

    return source.environment?.code ?? source.query?.environment ?? null;
}

export function environmentQueryParams(source, extra = {}) {
    const environment = environmentCodeFrom(source);

    return environment ? { ...extra, environment } : extra;
}

export function entityRouteQuery(entity) {
    return environmentQueryParams(entity);
}
