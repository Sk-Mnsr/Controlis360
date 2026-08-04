const NAME_TO_ISO = {
    senegal: 'sn',
    sénégal: 'sn',
    togo: 'tg',
    'cote divoire': 'ci',
    "cote d'ivoire": 'ci',
    "côte d'ivoire": 'ci',
    'cote-divoire': 'ci',
    mali: 'ml',
    'burkina faso': 'bf',
    benin: 'bj',
    bénin: 'bj',
    niger: 'ne',
    nigéria: 'ng',
    nigeria: 'ng',
    guinee: 'gn',
    guinée: 'gn',
    gabon: 'ga',
    cameroun: 'cm',
    congo: 'cg',
    'rd congo': 'cd',
    france: 'fr',
};

/**
 * Resolve ISO 3166-1 alpha-2 from environment code or country name.
 */
export function resolveCountryIso(codeOrName) {
    if (!codeOrName) {
        return null;
    }

    const raw = String(codeOrName).trim();
    if (!raw) {
        return null;
    }

    if (/^[a-z]{2}$/i.test(raw)) {
        return raw.toLowerCase();
    }

    const normalized = raw
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/['’]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    return NAME_TO_ISO[normalized] ?? NAME_TO_ISO[normalized.replace(/ /g, '')] ?? null;
}

export function countryFlagUrl(codeOrName, width = 40) {
    const iso = resolveCountryIso(codeOrName);
    if (!iso) {
        return null;
    }

    return `https://flagcdn.com/w${width}/${iso}.png`;
}
