import { ref, watch } from 'vue';
import { useEncheresClientAuthStore } from '../stores/encheresClientAuth';

function storageKey(email) {
    return `encheresnFavorites:${String(email || 'invite').toLowerCase()}`;
}

function readIds(email) {
    try {
        const raw = JSON.parse(localStorage.getItem(storageKey(email)) || '[]');
        return Array.isArray(raw) ? raw.map((id) => String(id)) : [];
    } catch {
        return [];
    }
}

export function useEncheresFavorites() {
    const auth = useEncheresClientAuthStore();
    const ids = ref(readIds(auth.client?.email));

    watch(
        () => auth.client?.email,
        (email) => {
            ids.value = readIds(email);
        },
    );

    function isFavorite(id) {
        return ids.value.includes(String(id));
    }

    function toggle(id) {
        const value = String(id);
        const next = isFavorite(value)
            ? ids.value.filter((item) => item !== value)
            : [...ids.value, value];
        localStorage.setItem(storageKey(auth.client?.email), JSON.stringify(next));
        ids.value = next;
    }

    return { ids, isFavorite, toggle };
}
