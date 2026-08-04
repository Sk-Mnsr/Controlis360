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
