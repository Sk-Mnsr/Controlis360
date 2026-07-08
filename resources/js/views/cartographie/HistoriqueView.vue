<template>
    <div class="historique-page">
        <div class="historique-actions">
            <RouterLink
                :to="{ name: 'cartographie.departement-analyse', params: { code: route.params.code } }"
                class="historique-back"
            >
                ← Retour à l'analyse
            </RouterLink>
        </div>

        <div v-if="loading" class="historique-loading">Chargement...</div>

        <template v-else>
            <p v-if="error" class="historique-error">{{ error }}</p>

            <section v-else class="historique-panel">
                <header class="historique-header">
                    <div>
                        <h1 class="historique-title">{{ title }}</h1>
                        <p class="historique-subtitle">Journal des actions sur les lignes de risque</p>
                    </div>
                    <button type="button" class="historique-refresh" @click="loadHistorique">
                        Actualiser
                    </button>
                </header>

                <div v-if="!logs.length" class="historique-empty">
                    Aucune action enregistrée pour cette entité.
                </div>

                <table v-else class="historique-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Utilisateur</th>
                            <th>Sous-processus</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs" :key="log.id">
                            <td class="historique-date">{{ log.created_at }}</td>
                            <td>
                                <span class="historique-badge" :class="`historique-badge--${log.action}`">
                                    {{ log.action_label }}
                                </span>
                            </td>
                            <td>{{ log.user?.name ?? '—' }}</td>
                            <td>{{ rowLabel(log) }}</td>
                            <td class="historique-message">{{ log.message || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </template>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import { useCartographieStore } from '../../stores/cartographie';
import { environmentQueryParams } from '../../utils/entityEnvironment';

const route = useRoute();
const cartographie = useCartographieStore();

const loading = ref(true);
const error = ref('');
const title = ref('');
const logs = ref([]);

function extractPayload(data) {
    const root = data?.data ?? data;

    return {
        title: root?.title ?? '',
        logs: root?.logs ?? [],
    };
}

function rowLabel(log) {
    if (log.row?.sub_process_name) {
        return log.row.sub_process_name;
    }

    return log.metadata?.sub_process_name ?? '—';
}

async function loadHistorique() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/referentials/analyse-risques/${route.params.code}/historique`, {
            params: environmentQueryParams(route),
        });
        const payload = extractPayload(data);
        title.value = payload.title;
        logs.value = payload.logs;
        cartographie.selectedEntityCode = route.params.code;
    } catch {
        error.value = 'Impossible de charger l\'historique.';
    } finally {
        loading.value = false;
    }
}

watch(() => [route.params.code, route.query.environment], loadHistorique);

onMounted(loadHistorique);
</script>

<style scoped>
.historique-page {
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.historique-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.historique-back {
    font-size: 0.875rem;
    color: #64748b;
    transition: color 0.15s;
}

.historique-back:hover {
    color: #0f172a;
}

.historique-loading,
.historique-empty {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: #64748b;
}

.historique-error {
    border-radius: 0.5rem;
    background: #fef2f2;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #b91c1c;
}

.historique-panel {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #fff;
    overflow: hidden;
}

.historique-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.historique-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
}

.historique-subtitle {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #64748b;
}

.historique-refresh {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #334155;
    background: #fff;
    cursor: pointer;
}

.historique-refresh:hover {
    background: #f8fafc;
}

.historique-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.historique-table th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 500;
    color: #475569;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.historique-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
    color: #334155;
}

.historique-date {
    white-space: nowrap;
    color: #64748b;
}

.historique-message {
    max-width: 24rem;
    word-break: break-word;
}

.historique-badge {
    display: inline-block;
    border-radius: 9999px;
    padding: 0.125rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 500;
    background: #f1f5f9;
    color: #475569;
}

.historique-badge--created {
    background: #dcfce7;
    color: #166534;
}

.historique-badge--updated {
    background: #e0f2fe;
    color: #075985;
}

.historique-badge--submitted {
    background: #fef3c7;
    color: #92400e;
}

.historique-badge--revision_requested {
    background: #ffedd5;
    color: #9a3412;
}

.historique-badge--validated {
    background: #ede9fe;
    color: #5b21b6;
}

.historique-badge--completed {
    background: #d1fae5;
    color: #065f46;
}

.historique-badge--deleted {
    background: #fee2e2;
    color: #991b1b;
}
</style>
