<template>
    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Réception</h2>
            <p class="mt-1 text-sm text-slate-500">
                Fiches adressées à votre département (« Établi par »).
            </p>

            <div class="mt-4">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher par n° fiche, type ou département..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    @input="debouncedLoad"
                >
            </div>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Chargement...
        </div>

        <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
            {{ error }}
        </div>

        <div v-else-if="!fiches.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
            Aucune fiche reçue pour votre département.
        </div>

        <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">N° fiche</th>
                        <th class="px-4 py-3 font-medium">Type de reporting</th>
                        <th class="px-4 py-3 font-medium">Établi par</th>
                        <th class="px-4 py-3 font-medium">Département</th>
                        <th class="px-4 py-3 font-medium">Statut</th>
                        <th class="px-4 py-3 font-medium">Émetteur</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="fiche in fiches" :key="fiche.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ fiche.fiche_number || '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            <span class="line-clamp-2">{{ fiche.type_reporting || '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ fiche.etabli_par || '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ fiche.etabli_par_entity?.name || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ statusLabel(fiche.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ fiche.creator?.name || '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <RouterLink
                                :to="{ name: 'conformite.reporting.reception.show', params: { id: fiche.id } }"
                                class="font-medium text-[#a3181f] hover:underline"
                            >
                                Ouvrir
                            </RouterLink>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import api from '../../api/client';

const fiches = ref([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
let debounceTimer = null;

function statusLabel(status) {
    if (status === 'en_traitement') return 'En traitement';
    if (status === 'envoyee') return 'Reçue';
    return status || '—';
}

function extractList(payload) {
    const data = payload?.data ?? payload;
    if (Array.isArray(data)) return data;
    if (Array.isArray(data?.data)) return data.data;
    return [];
}

async function loadFiches() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/regulatory-reporting-fiches/inbox', {
            params: { search: search.value || undefined, per_page: 50 },
        });
        fiches.value = extractList(data);
    } catch (err) {
        const payload = err.response?.data;
        error.value = payload?.message
            || Object.values(payload?.errors ?? payload?.data ?? {}).flat().join(' ')
            || 'Impossible de charger la réception.';
        fiches.value = [];
    } finally {
        loading.value = false;
    }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadFiches, 300);
}

onMounted(loadFiches);
onUnmounted(() => clearTimeout(debounceTimer));
</script>
