import { computed, ref } from 'vue';
import api from '../api/client';
import {
    deadlineBadgeStyle,
    normalizeColorToHex,
    normalizeParametrageColors,
    statusBadgeStyle,
    textColorStyle,
} from '../utils/mission-display-styles';

const settings = ref(null);
const loading = ref(false);
const loaded = ref(false);

const DEFAULT_DEADLINE_RULES = {
    near_threshold_days: 10,
    items: [
        { key: 'late', label: 'En retard', text_color: '#dc2626', badge_color: '#dc2626' },
        { key: 'warning', label: 'Proche échéance', text_color: '#ca8a04', badge_color: '#ea580c' },
        { key: 'ok', label: 'Dans les délais', text_color: '#334155', badge_color: '#047857' },
        { key: 'neutral', label: '—', text_color: '#64748b', badge_color: '#64748b' },
    ],
};

function extractPayload(data) {
    return normalizeParametrageColors(data?.data ?? data ?? null);
}

function deadlineRules() {
    return settings.value?.deadline_rules ?? DEFAULT_DEADLINE_RULES;
}

function deadlineItem(key) {
    return deadlineRules().items?.find((item) => item.key === key) ?? null;
}

export function useMissionParametrage() {
    async function loadMissionParametrage(force = false) {
        if (loaded.value && !force) {
            return settings.value;
        }

        loading.value = true;

        try {
            const { data } = await api.get('/mission-parametrage');
            settings.value = extractPayload(data);
            loaded.value = true;
            return settings.value;
        } finally {
            loading.value = false;
        }
    }

    async function saveMissionParametrage(payload) {
        const { data } = await api.put('/mission-parametrage', payload);
        settings.value = extractPayload(data);
        loaded.value = true;
        return settings.value;
    }

    function nearDeadlineThreshold() {
        return Number(deadlineRules().near_threshold_days ?? 10);
    }

    function remainingDaysTextStyle(remainingDays) {
        if (remainingDays === null || remainingDays === undefined) {
            return textColorStyle(deadlineItem('neutral')?.text_color ?? '#64748b');
        }

        if (remainingDays < 0) {
            return textColorStyle(deadlineItem('late')?.text_color ?? '#dc2626');
        }

        if (remainingDays <= nearDeadlineThreshold()) {
            return textColorStyle(deadlineItem('warning')?.text_color ?? '#ca8a04');
        }

        return textColorStyle(deadlineItem('ok')?.text_color ?? '#334155');
    }

    function deadlineToneStyle(tone) {
        const item = deadlineItem(tone);
        return deadlineBadgeStyle(item?.badge_color ?? '#64748b');
    }

    function deadlineLabel(tone) {
        return deadlineItem(tone)?.label ?? '—';
    }

    function missionStatusLabel(value) {
        const item = settings.value?.statuses?.find((status) => status.value === value);
        return item?.label ?? value;
    }

    function recommendationStatusLabel(value) {
        const item = settings.value?.recommendation_statuses?.find((status) => status.value === value);
        return item?.label ?? value;
    }

    function missionStatusStyle(value) {
        const item = settings.value?.statuses?.find((status) => status.value === value);
        return statusBadgeStyle(item?.color ?? '#64748b');
    }

    function recommendationStatusStyle(value) {
        const item = settings.value?.recommendation_statuses?.find((status) => status.value === value);
        return statusBadgeStyle(item?.color ?? '#64748b');
    }

    function resolveDeadlineStatus(remainingDays) {
        if (remainingDays === null || remainingDays === undefined) {
            return { label: deadlineLabel('neutral'), tone: 'neutral' };
        }

        if (remainingDays < 0) {
            return { label: deadlineLabel('late'), tone: 'late' };
        }

        if (remainingDays <= nearDeadlineThreshold()) {
            return { label: deadlineLabel('warning'), tone: 'warning' };
        }

        return { label: deadlineLabel('ok'), tone: 'ok' };
    }

    return {
        settings: computed(() => settings.value),
        loading: computed(() => loading.value),
        loadMissionParametrage,
        saveMissionParametrage,
        nearDeadlineThreshold,
        remainingDaysTextStyle,
        deadlineToneStyle,
        deadlineLabel,
        missionStatusLabel,
        recommendationStatusLabel,
        missionStatusStyle,
        recommendationStatusStyle,
        resolveDeadlineStatus,
        normalizeColorToHex,
    };
}
