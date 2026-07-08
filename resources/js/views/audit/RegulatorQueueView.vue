<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold">File régulateur</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Recommandations transmises par l'audit ou le contrôle pour avis avant clôture.
                    </p>
                </div>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher…"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-500 sm:max-w-xs"
                />
            </div>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement…
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-600">
            {{ error }}
        </div>

        <div v-else-if="!items.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">Aucune recommandation transmise pour le moment.</p>
        </div>

        <div v-else-if="!filteredItems.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">Aucun résultat pour votre recherche.</p>
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-700">
                        <th class="px-4 py-3 font-semibold">Mission</th>
                        <th class="px-4 py-3 font-semibold">Recommandation</th>
                        <th class="px-4 py-3 font-semibold">Département(s)</th>
                        <th class="px-4 py-3 font-semibold">Thème</th>
                        <th class="px-4 py-3 font-semibold">Statut</th>
                        <th class="px-4 py-3 font-semibold">Transmis le</th>
                        <th class="px-4 py-3 font-semibold">Par</th>
                        <th class="px-4 py-3 font-semibold text-center">Commentaires</th>
                        <th class="w-24 px-4 py-3 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="item in filteredItems"
                        :key="item.id"
                        class="transition hover:bg-slate-50/80"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ item.mission_reference || '—' }}</p>
                            <p class="text-xs text-slate-500">{{ item.mission_type_fr || '—' }}</p>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ item.reference }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ item.entity_name || '—' }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ item.theme || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                {{ item.status_fr || '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ formatDateTime(item.regulator_transmitted_at) }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ item.regulator_transmitted_by_name || '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-800">{{ item.comments_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            <RouterLink
                                :to="{ name: 'audit.regulator.show', params: { recoId: item.id } }"
                                class="inline-flex rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-800"
                            >
                                Voir
                            </RouterLink>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../api/client';

const loading = ref(true);
const error = ref('');
const items = ref([]);
const search = ref('');

const filteredItems = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (!query) return items.value;

    return items.value.filter((item) => [
        item.mission_reference,
        item.reference,
        item.entity_name,
        item.theme,
        item.mission_type_fr,
        item.auditor,
        item.regulator_transmitted_by_name,
    ].some((value) => String(value ?? '').toLowerCase().includes(query)));
});

function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function loadQueue() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/recommendations/regulator-queue');
        items.value = data?.data ?? data ?? [];
    } catch {
        error.value = 'Impossible de charger la file régulateur.';
        items.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(loadQueue);
</script>
