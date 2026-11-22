<template>
    <div
        v-if="returns.length"
        class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"
    >
        <p class="font-semibold">Retour d'affectation</p>
        <div
            v-for="item in returns"
            :key="item.id"
            class="mt-3 border-t border-amber-200 pt-3 first:mt-2 first:border-t-0 first:pt-0"
        >
            <p class="text-xs font-medium text-amber-800">
                {{ item.author_name || 'Responsable' }}
                <span v-if="item.entity_name"> — {{ item.entity_name }}</span>
                <span v-if="item.created_at"> — {{ formatDisplayDate(item.created_at) }}</span>
            </p>
            <p class="mt-1 whitespace-pre-wrap">{{ item.avis }}</p>
        </div>
        <p v-if="showReassignHint" class="mt-3 text-xs text-amber-900">
            Cette recommandation n'est plus affectée. Ouvrez-la et modifiez le département pour la renvoyer au bon responsable.
        </p>
    </div>
</template>

<script setup>
defineProps({
    returns: { type: Array, default: () => [] },
    showReassignHint: { type: Boolean, default: false },
});

function formatDisplayDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        const [y, m, d] = String(value).split('-');
        return y && m && d ? `${d}/${m}/${y}` : String(value);
    }

    return date.toLocaleDateString('fr-FR');
}
</script>
