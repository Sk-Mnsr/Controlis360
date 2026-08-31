/** Codes ISO 3166-1 alpha-2 utiles pour les filiales COFINA (et quelques voisins). */
export const ENVIRONMENT_ISO_COUNTRIES = [
    { code: 'BJ', name: 'Bénin' },
    { code: 'BF', name: 'Burkina Faso' },
    { code: 'CI', name: "Côte d'Ivoire" },
    { code: 'CM', name: 'Cameroun' },
    { code: 'GA', name: 'Gabon' },
    { code: 'GH', name: 'Ghana' },
    { code: 'GN', name: 'Guinée' },
    { code: 'GW', name: 'Guinée-Bissau' },
    { code: 'GQ', name: 'Guinée équatoriale' },
    { code: 'ML', name: 'Mali' },
    { code: 'MR', name: 'Mauritanie' },
    { code: 'NE', name: 'Niger' },
    { code: 'NG', name: 'Nigeria' },
    { code: 'CG', name: 'Congo' },
    { code: 'CD', name: 'RD Congo' },
    { code: 'SN', name: 'Sénégal' },
    { code: 'TD', name: 'Tchad' },
    { code: 'TG', name: 'Togo' },
];

/** Alias de noms → code ISO (pour suggestion automatique). */
const NAME_TO_ISO = {
    senegal: 'SN',
    sénégal: 'SN',
    togo: 'TG',
    'cote divoire': 'CI',
    "cote d'ivoire": 'CI',
    "côte d'ivoire": 'CI',
    'cote-divoire': 'CI',
    benin: 'BJ',
    bénin: 'BJ',
    'burkina faso': 'BF',
    mali: 'ML',
    guinee: 'GN',
    guinée: 'GN',
    ghana: 'GH',
    niger: 'NE',
    nigeria: 'NG',
    cameroun: 'CM',
    gabon: 'GA',
    tchad: 'TD',
    mauritanie: 'MR',
};

function normalizeNameKey(name) {
    return String(name ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/['’]/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}

export function suggestIsoCodeFromName(name) {
    const key = normalizeNameKey(name);
    if (!key) return '';

    if (NAME_TO_ISO[key]) return NAME_TO_ISO[key];

    const match = ENVIRONMENT_ISO_COUNTRIES.find((country) => (
        normalizeNameKey(country.name) === key
    ));

    return match?.code ?? '';
}

export function normalizeEnvironmentCode(code) {
    return String(code ?? '')
        .trim()
        .toUpperCase()
        .replace(/\s+/g, '_')
        .replace(/[^A-Z0-9_]/g, '');
}

export function isoFromEnvironmentCode(code) {
    const normalized = normalizeEnvironmentCode(code);
    if (!normalized) return '';

    if (ENVIRONMENT_ISO_COUNTRIES.some((country) => country.code === normalized)) {
        return normalized;
    }

    const prefix = normalized.split('_')[0];
    return ENVIRONMENT_ISO_COUNTRIES.some((country) => country.code === prefix) ? prefix : '';
}

/**
 * Code unique : ISO libre (SN) ou ISO + nom (SN_CTI) si le pays a déjà un environnement.
 */
export function uniqueEnvironmentCode(requested, name, existingCodes = [], exceptCode = '') {
    const taken = new Set(
        existingCodes
            .map((code) => normalizeEnvironmentCode(code))
            .filter((code) => code && code !== normalizeEnvironmentCode(exceptCode)),
    );

    const requestedCode = normalizeEnvironmentCode(requested);
    if (requestedCode && !taken.has(requestedCode)) {
        return requestedCode;
    }

    const iso = isoFromEnvironmentCode(requestedCode) || suggestIsoCodeFromName(name);
    const slug = slugWithoutCountry(name, iso);
    const bases = [];

    if (iso && slug) bases.push(`${iso}_${slug}`);
    if (slug) bases.push(slug);
    if (iso) bases.push(iso);

    for (const base of bases) {
        if (base && !taken.has(base)) return base;
    }

    const prefix = iso || slug || 'ENV';
    let suffix = 2;
    let candidate = `${prefix}_${suffix}`;
    while (taken.has(candidate)) {
        suffix += 1;
        candidate = `${prefix}_${suffix}`;
    }

    return candidate;
}

function slugWithoutCountry(name, iso) {
    let slug = normalizeEnvironmentCode(name);
    if (!slug) return '';

    if (iso) {
        slug = slug.replace(new RegExp(`(^|_)${iso}(_|$)`, 'g'), '_');
        const country = ENVIRONMENT_ISO_COUNTRIES.find((item) => item.code === iso);
        if (country) {
            const countrySlug = normalizeEnvironmentCode(country.name);
            if (countrySlug) {
                slug = slug.replace(new RegExp(`(^|_)${countrySlug}(_|$)`, 'g'), '_');
            }
        }
    }

    return slug.replace(/^_+|_+$/g, '').replace(/_+/g, '_');
}
