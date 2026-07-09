export const COLOR_OPTIONS = [
    { value: 'blue', label: 'Bleu', hex: '#2563eb' },
    { value: 'amber', label: 'Ambre', hex: '#d97706' },
    { value: 'orange', label: 'Orange', hex: '#ea580c' },
    { value: 'yellow', label: 'Jaune', hex: '#ca8a04' },
    { value: 'red', label: 'Rouge', hex: '#dc2626' },
    { value: 'emerald', label: 'Vert', hex: '#047857' },
    { value: 'slate', label: 'Gris', hex: '#64748b' },
    { value: 'violet', label: 'Violet', hex: '#7c3aed' },
];

const SLUG_TO_HEX = Object.fromEntries(COLOR_OPTIONS.map((item) => [item.value, item.hex]));

const TEXT_COLOR_CLASSES = {
    blue: 'text-blue-600 font-semibold',
    amber: 'text-amber-600 font-semibold',
    orange: 'text-orange-600 font-semibold',
    yellow: 'text-yellow-600 font-semibold',
    red: 'text-red-600 font-semibold',
    emerald: 'text-emerald-700 font-medium',
    slate: 'text-slate-800 font-medium',
    violet: 'text-violet-600 font-semibold',
};

const BADGE_COLOR_CLASSES = {
    blue: 'bg-blue-100 text-blue-800',
    amber: 'bg-amber-100 text-amber-800',
    orange: 'bg-orange-500 text-white',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-500 text-white',
    emerald: 'bg-emerald-500 text-white',
    slate: 'bg-slate-200 text-slate-700',
    violet: 'bg-violet-100 text-violet-800',
};

const SOLID_BADGE_COLOR_CLASSES = {
    blue: 'bg-blue-500 text-white',
    amber: 'bg-amber-500 text-white',
    orange: 'bg-orange-500 text-white',
    yellow: 'bg-yellow-500 text-white',
    red: 'bg-red-500 text-white',
    emerald: 'bg-emerald-500 text-white',
    slate: 'bg-slate-500 text-white',
    violet: 'bg-violet-500 text-white',
};

export function isHexColor(color) {
    return typeof color === 'string' && /^#[0-9A-Fa-f]{6}$/.test(color);
}

export function normalizeColorToHex(color, fallback = '#64748b') {
    if (!color) {
        return fallback;
    }

    if (isHexColor(color)) {
        return color.toLowerCase();
    }

    return SLUG_TO_HEX[color] ?? fallback;
}

function hexToRgba(hex, alpha) {
    const normalized = normalizeColorToHex(hex).replace('#', '');
    const r = Number.parseInt(normalized.slice(0, 2), 16);
    const g = Number.parseInt(normalized.slice(2, 4), 16);
    const b = Number.parseInt(normalized.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export function textColorStyle(color) {
    return {
        color: normalizeColorToHex(color),
        fontWeight: 600,
    };
}

export function softBadgeStyle(color) {
    const hex = normalizeColorToHex(color);

    return {
        backgroundColor: hexToRgba(hex, 0.15),
        color: hex,
    };
}

export function solidBadgeStyle(color) {
    const hex = normalizeColorToHex(color);

    return {
        backgroundColor: hex,
        color: '#ffffff',
    };
}

export function textColorClass(color) {
    if (isHexColor(color)) {
        return '';
    }

    return TEXT_COLOR_CLASSES[color] ?? TEXT_COLOR_CLASSES.slate;
}

export function badgeColorClass(color, solid = false) {
    if (isHexColor(color)) {
        return '';
    }

    const map = solid ? SOLID_BADGE_COLOR_CLASSES : BADGE_COLOR_CLASSES;
    return map[color] ?? map.slate;
}

export function statusBadgeClass(color) {
    if (isHexColor(color)) {
        return 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium';
    }

    return `inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${badgeColorClass(color)}`;
}

export function statusBadgeStyle(color) {
    return softBadgeStyle(color);
}

export function deadlineBadgeClass(color) {
    if (isHexColor(color)) {
        return 'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold';
    }

    return `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${badgeColorClass(color, true)}`;
}

export function deadlineBadgeStyle(color) {
    return solidBadgeStyle(color);
}

export function normalizeParametrageColors(settings) {
    if (!settings) {
        return settings;
    }

    const normalizeStatus = (status) => ({
        ...status,
        color: normalizeColorToHex(status.color),
    });

    const normalizeDeadlineItem = (item) => ({
        ...item,
        text_color: normalizeColorToHex(item.text_color),
        badge_color: normalizeColorToHex(item.badge_color),
    });

    return {
        ...settings,
        statuses: (settings.statuses ?? []).map(normalizeStatus),
        recommendation_statuses: (settings.recommendation_statuses ?? []).map(normalizeStatus),
        deadline_rules: {
            ...settings.deadline_rules,
            items: (settings.deadline_rules?.items ?? []).map(normalizeDeadlineItem),
        },
    };
}
