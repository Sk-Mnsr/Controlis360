<template>
    <div class="auc">
        <header class="auc-head">
            <div>
                <h3>Mettre en enchère</h3>
                <p>Publiez, suspendez ou supprimez les biens. Les biens publiés apparaissent sur le front Client.</p>
            </div>
            <RouterLink :to="{ name: 'vente-encheres.admin.deposit' }" class="auc-btn auc-btn--primary">
                + Nouveau bien
            </RouterLink>
        </header>

        <div class="auc-filters">
            <select v-model="statusFilter" class="auc-input">
                <option value="">Tous les statuts</option>
                <option value="published">Publiés</option>
                <option value="draft">Brouillons</option>
            </select>
            <input v-model="search" type="search" class="auc-input" placeholder="Rechercher un lot ou titre…" />
        </div>

        <div class="auc-table-wrap">
            <table class="auc-table">
                <thead>
                    <tr>
                        <th>Lot</th>
                        <th>Bien</th>
                        <th>Catégorie</th>
                        <th>Fin</th>
                        <th>Prix</th>
                        <th>Admin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in filtered" :key="item.id">
                        <td>{{ item.lot }}</td>
                        <td>
                            <strong>{{ item.title }}</strong>
                            <p>{{ item.location }}</p>
                        </td>
                        <td>{{ item.category_label }}</td>
                        <td>{{ formatDate(item.ends_at) }}</td>
                        <td>{{ formatFcfa(item.current_price) }}</td>
                        <td>
                            <span class="auc-badge" :class="item.admin_status">{{ item.admin_status }}</span>
                        </td>
                        <td class="auc-actions">
                            <button
                                v-if="item.admin_status !== 'published'"
                                type="button"
                                class="auc-mini auc-mini--ok"
                                @click="publish(item.id)"
                            >
                                Publier
                            </button>
                            <button
                                v-else
                                type="button"
                                class="auc-mini"
                                @click="unpublish(item.id)"
                            >
                                Dépublier
                            </button>
                            <RouterLink
                                class="auc-mini auc-mini--view"
                                :to="{ name: 'vente-encheres.admin.auctions.show', params: { id: item.id } }"
                            >
                                Voir
                            </RouterLink>
                            <RouterLink
                                class="auc-mini auc-mini--edit"
                                :to="{ name: 'vente-encheres.admin.deposit', params: { id: item.id } }"
                            >
                                Modifier
                            </RouterLink>
                            <button type="button" class="auc-mini auc-mini--danger" @click="remove(item)">
                                Suppr.
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!filtered.length" class="auc-empty">Aucun bien trouvé.</p>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { formatFcfa } from '../../../config/encheres-mock-data';
import { useEncheresAdminStore } from '../../../stores/encheresAdmin';

const store = useEncheresAdminStore();
const statusFilter = ref('');
const search = ref('');

const filtered = computed(() => {
    let list = [...store.auctions];
    if (statusFilter.value) {
        list = list.filter((item) => item.admin_status === statusFilter.value);
    }
    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter((item) =>
            item.title?.toLowerCase().includes(q)
            || item.lot?.toLowerCase().includes(q)
            || item.category_label?.toLowerCase().includes(q),
        );
    }
    return list;
});

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
}

function publish(id) {
    store.publishAuction(id);
}

function unpublish(id) {
    store.unpublishAuction(id);
}

function remove(item) {
    if (!window.confirm(`Supprimer ${item.lot} ?`)) return;
    store.deleteAuction(item.id);
}
</script>

<style scoped>
.auc-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.auc-head h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
}

.auc-head p {
    margin: 0.25rem 0 0;
    color: #64748b;
    font-size: 0.875rem;
}

.auc-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
}

.auc-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.875rem;
    background: #fff;
    min-width: 180px;
}

.auc-table-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    overflow: auto;
}

.auc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.auc-table th,
.auc-table td {
    border-bottom: 1px solid #e2e8f0;
    padding: 0.65rem 0.55rem;
    text-align: left;
    vertical-align: top;
}

.auc-table th {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #64748b;
}

.auc-table td p {
    margin: 0.15rem 0 0;
    color: #64748b;
    font-size: 0.75rem;
}

.auc-badge {
    display: inline-block;
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
}

.auc-badge.published {
    background: #dcfce7;
    color: #166534;
}

.auc-badge.draft {
    background: #fef3c7;
    color: #92400e;
}

.auc-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.auc-btn,
.auc-mini {
    border-radius: 0.4rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.35rem 0.55rem;
    cursor: pointer;
}

.auc-btn--primary,
.auc-mini--ok {
    border-color: #16a34a;
    background: #16a34a;
    color: #fff;
}

.auc-mini--view {
    border-color: #2563eb;
    color: #1d4ed8;
}

.auc-mini--edit {
    border-color: #d97706;
    color: #b45309;
}

.auc-mini--danger {
    border-color: #fecaca;
    color: #b91c1c;
}

.auc-empty {
    padding: 1.5rem;
    text-align: center;
    color: #64748b;
}
</style>
