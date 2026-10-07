import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

function partsFromMs(ms) {
    if (ms <= 0) {
        return { ended: true, days: 0, hours: 0, minutes: 0, seconds: 0, totalMs: 0 };
    }

    const totalSeconds = Math.floor(ms / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return { ended: false, days, hours, minutes, seconds, totalMs: ms };
}

export function useEncheresCountdown(endsAtRef) {
    const now = ref(Date.now());
    let timer = null;

    onMounted(() => {
        timer = window.setInterval(() => {
            now.value = Date.now();
        }, 1000);
    });

    onBeforeUnmount(() => {
        if (timer) {
            clearInterval(timer);
        }
    });

    const countdown = computed(() => {
        const endsAt = endsAtRef?.value ?? endsAtRef;
        if (!endsAt) {
            return partsFromMs(0);
        }
        const target = new Date(endsAt).getTime();
        return partsFromMs(target - now.value);
    });

    const label = computed(() => {
        const c = countdown.value;
        if (c.ended) {
            return 'TERMINÉE';
        }
        if (c.days > 0) {
            return `${pad(c.days)} J : ${pad(c.hours)} H : ${pad(c.minutes)} MIN : ${pad(c.seconds)} SEC`;
        }
        return `${pad(c.hours)} H : ${pad(c.minutes)} MIN : ${pad(c.seconds)} SEC`;
    });

    const shortLabel = computed(() => {
        const c = countdown.value;
        if (c.ended) {
            return 'Terminée';
        }
        if (c.days > 0) {
            return `${c.days}j ${pad(c.hours)}h ${pad(c.minutes)}m`;
        }
        return `${pad(c.hours)}h ${pad(c.minutes)}m ${pad(c.seconds)}s`;
    });

    return { countdown, label, shortLabel };
}

function pad(n) {
    return String(n).padStart(2, '0');
}
