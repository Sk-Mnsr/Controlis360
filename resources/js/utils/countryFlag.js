/**
 * Fallback when environment code is not a 2-letter ISO.
 * Prefer storing ISO codes on environments (SN, CI, TG…) — flags resolve automatically.
 */
const NAME_TO_ISO = {
    // Afrique de l'Ouest
    senegal: 'sn',
    sénégal: 'sn',
    togo: 'tg',
    'cote divoire': 'ci',
    "cote d'ivoire": 'ci',
    "côte d'ivoire": 'ci',
    'cote-divoire': 'ci',
    cotedivoire: 'ci',
    mali: 'ml',
    'burkina faso': 'bf',
    burkinafaso: 'bf',
    burkina: 'bf',
    benin: 'bj',
    bénin: 'bj',
    niger: 'ne',
    nigeria: 'ng',
    nigéria: 'ng',
    guinee: 'gn',
    guinée: 'gn',
    'guinee-bissau': 'gw',
    'guinee bissau': 'gw',
    'guinée-bissau': 'gw',
    'guinée bissau': 'gw',
    'guinea-bissau': 'gw',
    'cap vert': 'cv',
    'cap-vert': 'cv',
    'cabo verde': 'cv',
    gambie: 'gm',
    gambia: 'gm',
    libería: 'lr',
    liberia: 'lr',
    'sierra leone': 'sl',
    sierraleone: 'sl',
    mauritanie: 'mr',
    mauritania: 'mr',
    ghana: 'gh',

    // Afrique centrale
    gabon: 'ga',
    cameroun: 'cm',
    cameroon: 'cm',
    congo: 'cg',
    'congo brazzaville': 'cg',
    'république du congo': 'cg',
    'republique du congo': 'cg',
    'rd congo': 'cd',
    'rdc': 'cd',
    'republique democratique du congo': 'cd',
    'république démocratique du congo': 'cd',
    'congo kinshasa': 'cd',
    tchad: 'td',
    chad: 'td',
    'centrafrique': 'cf',
    'republique centrafricaine': 'cf',
    'république centrafricaine': 'cf',
    'guinee equatoriale': 'gq',
    'guinée équatoriale': 'gq',
    'sao tome-et-principe': 'st',
    'sao tome et principe': 'st',
    'são tomé-et-principe': 'st',

    // Afrique de l'Est
    kenya: 'ke',
    ouganda: 'ug',
    uganda: 'ug',
    tanzanie: 'tz',
    tanzania: 'tz',
    rwanda: 'rw',
    burundi: 'bi',
    ethiopie: 'et',
    éthiopie: 'et',
    ethiopia: 'et',
    erythrée: 'er',
    érythrée: 'er',
    eritrea: 'er',
    djibouti: 'dj',
    somalie: 'so',
    somalia: 'so',
    'soudan du sud': 'ss',
    'south sudan': 'ss',
    soudan: 'sd',
    sudan: 'sd',
    madagascar: 'mg',
    maurice: 'mu',
    mauritius: 'mu',
    seychelles: 'sc',
    comores: 'km',
    'union des comores': 'km',

    // Afrique australe
    'afrique du sud': 'za',
    'south africa': 'za',
    namibie: 'na',
    namibia: 'na',
    botswana: 'bw',
    zimbabwe: 'zw',
    zambie: 'zm',
    zambia: 'zm',
    malawi: 'mw',
    mozambique: 'mz',
    angola: 'ao',
    eswatini: 'sz',
    swaziland: 'sz',
    lesotho: 'ls',

    // Afrique du Nord
    maroc: 'ma',
    morocco: 'ma',
    algerie: 'dz',
    algérie: 'dz',
    algeria: 'dz',
    tunisie: 'tn',
    tunisia: 'tn',
    libye: 'ly',
    libya: 'ly',
    egypte: 'eg',
    égypte: 'eg',
    egypt: 'eg',

    // Autre (hors Afrique, déjà utilisé)
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

    // Environment code already ISO → flag works for any country
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
