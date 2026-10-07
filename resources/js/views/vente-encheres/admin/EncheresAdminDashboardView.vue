<template>
    <div class="dash">
        <div class="dash-grid">
            <article class="dash-card">
                <p>Enchères totales</p>
                <strong>{{ stats.auctions_total }}</strong>
            </article>
            <article class="dash-card">
                <p>En cours</p>
                <strong>{{ stats.auctions_live }}</strong>
            </article>
            <article class="dash-card">
                <p>Brouillons</p>
                <strong>{{ stats.auctions_draft }}</strong>
            </article>
            <article class="dash-card">
                <p>Catégories</p>
                <strong>{{ stats.categories_total }}</strong>
            </article>
            <article class="dash-card dash-card--wide">
                <p>Volume enchères actuelles</p>
                <strong>{{ formatFcfa(stats.volume_total) }}</strong>
            </article>
        </div>

        <section class="dash-panel">
            <h3>Actions rapides</h3>
            <div class="dash-actions">
                <RouterLink :to="{ name: 'vente-encheres.admin.deposit' }" class="dash-btn dash-btn--primary">
                    Déposer un bien
                </RouterLink>
                <RouterLink :to="{ name: 'vente-encheres.admin.auctions' }" class="dash-btn">
                    Gérer les enchères
                </RouterLink>
                <RouterLink :to="{ name: 'vente-encheres.admin.categories' }" class="dash-btn">
                    Paramétrer catégories
                </RouterLink>
                <RouterLink :to="{ name: 'vente-encheres.client.home' }" class="dash-btn">
                    Voir le front Client
                </RouterLink>
            </div>
        </section>

        <section class="dash-panel">
            <h3>Derniers biens</h3>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Lot</th>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Statut admin</th>
                        <th>Prix actuel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in recent" :key="item.id">
                        <td>{{ item.lot }}</td>
                        <td>{{ item.title }}</td>
                        <td>{{ item.category_label }}</td>
                        <td>
                            <span class="dash-badge" :class="item.admin_status">{{ item.admin_status }}</span>
                        </td>
                        <td>{{ formatFcfa(item.current_price) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatFcfa } from '../../../config/encheres-mock-data';
import { useEncheresAdminStore } from '../../../stores/encheresAdmin';

const store = useEncheresAdminStore();
const stats = computed(() => store.stats);
const recent = computed(() => store.auctions.slice(0, 8));
</script>

<style scoped>
.dash-grid {
    display: grid;
    gap: 0.85rem;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    margin-bottom: 1.25rem;
}

.dash-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
}

.dash-card p {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
}

.dash-card strong {
    display: block;
    margin-top: 0.35rem;
    font-size: 1.35rem;
    color: #0f172a;
}

.dash-card--wide {
    grid-column: span 2;
}

.dash-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.1rem;
    margin-bottom: 1rem;
}

.dash-panel h3 {
    margin: 0 0 0.85rem;
    font-size: 1rem;
    font-weight: 800;
}

.dash-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.dash-btn {
    border-radius: 0.45rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    text-decoration: none;
    font-size: 0.8125rem;
    font-weight: 700;
    padding: 0.55rem 0.85rem;
}

.dash-btn--primary {
    border-color: #f59e0b;
    background: #f59e0b;
    color: #0f172a;
}

.dash-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.dash-table th,
.dash-table td {
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    padding: 0.55rem 0.4rem;
}

.dash-table th {
    color: #64748b;
    font-size: 0.7rem;
    text-transform: uppercase;
}

.dash-badge {
    display: inline-block;
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
}

.dash-badge.published {
    background: #dcfce7;
    color: #166534;
}

.dash-badge.draft {
    background: #fef3c7;
    color: #92400e;
}

@media (max-width: 640px) {
    .dash-card--wide {
        grid-column: span 1;
    }
}
</style>
