<template>
    <section class="users-history">
        <div class="users-history-toolbar">
            <div class="users-history-heading">
                <h3>Historique des comptes</h3>
                <p>{{ totalLabel }}</p>
            </div>

            <div class="users-history-actions">
                <button type="button" class="users-history-btn" @click="loadUsers">
                    Actualiser
                </button>
            </div>
        </div>

        <div class="users-history-filters">
            <label class="users-history-field users-history-field-grow">
                <span>Recherche</span>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Nom ou e-mail…"
                    @input="onSearchInput"
                />
            </label>

            <label v-if="isSuperAdmin" class="users-history-field">
                <span>Environnement</span>
                <select v-model="environmentId" @change="applyFilters">
                    <option value="">Tous</option>
                    <option v-for="env in environments" :key="env.id" :value="String(env.id)">
                        {{ env.name }} ({{ env.code }})
                    </option>
                </select>
            </label>

            <label class="users-history-field">
                <span>Profil</span>
                <select v-model="profile" @change="applyFilters">
                    <option value="">Tous</option>
                    <option v-for="option in profileOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <label class="users-history-field">
                <span>Statut</span>
                <select v-model="activated" @change="applyFilters">
                    <option value="">Tous</option>
                    <option value="true">Actif</option>
                    <option value="false">Inactif</option>
                </select>
            </label>

            <label class="users-history-field">
                <span>Par page</span>
                <select v-model.number="perPage" @change="changePerPage">
                    <option :value="8">8</option>
                    <option :value="15">15</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
            </label>

            <button
                v-if="hasActiveFilters"
                type="button"
                class="users-history-reset"
                @click="resetFilters"
            >
                Réinitialiser
            </button>
        </div>

        <div v-if="loading" class="users-history-empty">Chargement…</div>

        <template v-else>
            <div class="users-history-table-wrap">
                <table class="users-history-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Profil</th>
                            <th v-if="isSuperAdmin">Environnement</th>
                            <th>Entité</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td>
                                <div class="users-history-identity">
                                    <span class="users-history-avatar" aria-hidden="true">
                                        {{ initials(user.name) }}
                                    </span>
                                    <div>
                                        <p class="users-history-name">{{ user.name }}</p>
                                        <p class="users-history-email">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="users-history-profile">{{ user.profile_fr }}</p>
                                <p v-if="roleLabel(user)" class="users-history-role">{{ roleLabel(user) }}</p>
                            </td>
                            <td v-if="isSuperAdmin">
                                <span class="users-history-chip">{{ environmentNames(user) }}</span>
                            </td>
                            <td>
                                <span class="users-history-chip muted">{{ entityNames(user) }}</span>
                            </td>
                            <td>
                                <span
                                    class="users-history-status"
                                    :class="user.activated ? 'is-active' : 'is-inactive'"
                                >
                                    {{ user.activated ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="users-history-menu" :class="{ open: openMenuId === user.id }">
                                    <button
                                        type="button"
                                        class="users-history-menu-trigger"
                                        :aria-expanded="openMenuId === user.id"
                                        aria-haspopup="menu"
                                        aria-label="Actions"
                                        @click.stop="toggleMenu(user, $event)"
                                    >
                                        <span aria-hidden="true">⋮</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!users.length">
                            <td :colspan="isSuperAdmin ? 6 : 5" class="users-history-empty-cell">
                                Aucun utilisateur ne correspond à ces critères.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="total > 0" class="users-history-pagination">
                <p>{{ fromItem }}–{{ toItem }} sur {{ total }}</p>
                <div class="users-history-pages">
                    <button type="button" :disabled="page <= 1 || loading" @click="goToPage(page - 1)">
                        Précédent
                    </button>
                    <button
                        v-for="pageNumber in visiblePages"
                        :key="pageNumber"
                        type="button"
                        :class="{ active: pageNumber === page }"
                        :disabled="loading"
                        @click="goToPage(pageNumber)"
                    >
                        {{ pageNumber }}
                    </button>
                    <button
                        type="button"
                        :disabled="page >= lastPage || loading"
                        @click="goToPage(page + 1)"
                    >
                        Suivant
                    </button>
                </div>
            </div>
        </template>

        <Teleport to="body">
            <div
                v-if="menuUser"
                class="users-history-menu-panel"
                role="menu"
                :style="menuStyle"
                @click.stop
            >
                <RouterLink
                    :to="{ name: 'users.edit', params: { id: menuUser.id } }"
                    class="users-history-menu-item"
                    role="menuitem"
                    @click="closeMenu"
                >
                    Modifier
                </RouterLink>
                <button
                    type="button"
                    class="users-history-menu-item"
                    role="menuitem"
                    @click="duplicateUser(menuUser)"
                >
                    Dupliquer
                </button>
                <button
                    type="button"
                    class="users-history-menu-item danger"
                    role="menuitem"
                    :disabled="menuUser.id === currentUserId"
                    :title="menuUser.id === currentUserId ? 'Vous ne pouvez pas supprimer votre propre compte' : 'Supprimer'"
                    @click="deleteUser(menuUser)"
                >
                    Supprimer
                </button>
            </div>
        </Teleport>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import { PROFILE_LABELS } from '../../config/module-access';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const isSuperAdmin = computed(() => (auth.baseUser?.profile ?? auth.user?.profile) === 'super_admin');
const currentUserId = computed(() => auth.baseUser?.id ?? auth.user?.id ?? null);

const loading = ref(true);
const users = ref([]);
const environments = ref([]);
const search = ref('');
const environmentId = ref('');
const profile = ref('');
const activated = ref('');
const page = ref(1);
const perPage = ref(8);
const lastPage = ref(1);
const total = ref(0);
const openMenuId = ref(null);
const menuUser = ref(null);
const menuStyle = ref({});

let searchTimer = null;

function toggleMenu(user, event) {
    if (openMenuId.value === user.id) {
        closeMenu();
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const openUp = window.innerHeight - rect.bottom < 160;

    menuStyle.value = {
        top: openUp ? `${rect.top - 4}px` : `${rect.bottom + 4}px`,
        right: `${window.innerWidth - rect.right}px`,
        transform: openUp ? 'translateY(-100%)' : 'none',
    };
    openMenuId.value = user.id;
    menuUser.value = user;
}

function closeMenu() {
    openMenuId.value = null;
    menuUser.value = null;
}

function onDocumentClick() {
    closeMenu();
}

function onWindowScrollOrResize() {
    closeMenu();
}

const profileOptions = computed(() => (
    Object.entries(PROFILE_LABELS).map(([value, label]) => ({ value, label }))
));

const hasActiveFilters = computed(() => Boolean(
    search.value.trim()
    || environmentId.value
    || profile.value
    || activated.value,
));

const totalLabel = computed(() => {
    if (loading.value) return 'Chargement de la liste…';
    if (!total.value) return 'Aucun compte trouvé';
    return `${total.value} compte${total.value > 1 ? 's' : ''} trouvé${total.value > 1 ? 's' : ''}`;
});

const fromItem = computed(() => {
    if (!total.value) return 0;
    return (page.value - 1) * perPage.value + 1;
});

const toItem = computed(() => Math.min(page.value * perPage.value, total.value));

const visiblePages = computed(() => {
    const pages = [];
    const start = Math.max(1, page.value - 2);
    const end = Math.min(lastPage.value, page.value + 2);

    for (let number = start; number <= end; number += 1) {
        pages.push(number);
    }

    return pages;
});

function initials(name) {
    return String(name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('') || '?';
}

function roleLabel(user) {
    return user.controle_role_fr
        || user.audit_role_fr
        || user.gouvernance_it_role_fr
        || user.metier_role_fr
        || '';
}

function extractPagination(responseData) {
    const root = responseData ?? {};
    const items = Array.isArray(root.data)
        ? root.data
        : (Array.isArray(root.data?.data) ? root.data.data : []);

    return {
        items,
        page: Number(root.current_page ?? root.data?.current_page ?? 1),
        lastPage: Number(root.last_page ?? root.data?.last_page ?? 1),
        total: Number(root.total ?? root.data?.total ?? items.length),
        perPage: Number(root.per_page ?? root.data?.per_page ?? perPage.value),
    };
}

function environmentNames(user) {
    const items = user.environments ?? user.Environments ?? [];
    if (items.length) {
        return items.map((environment) => environment.name).join(', ');
    }

    return user.environment?.name ?? user.Environment?.name ?? '—';
}

function entityNames(user) {
    const items = user.entities ?? user.Entities ?? [];
    if (items.length) {
        return items.map((entity) => entity.name).join(', ');
    }

    return user.entity?.name ?? user.Entity?.name ?? '—';
}

async function loadEnvironments() {
    if (!isSuperAdmin.value) return;

    try {
        const { data } = await api.get('/environments', {
            params: { paginate: 'false', order_by_asc: 'name' },
        });
        const root = data?.data ?? data;
        environments.value = Array.isArray(root)
            ? root
            : (Array.isArray(root?.data) ? root.data : []);
    } catch {
        environments.value = [];
    }
}

async function loadUsers() {
    loading.value = true;

    try {
        const { data } = await api.get('/users', {
            params: {
                page: page.value,
                per_page: perPage.value,
                search: search.value.trim() || undefined,
                environment_id: environmentId.value || undefined,
                profile: profile.value || undefined,
                activated: activated.value === '' ? undefined : activated.value,
                order_by_asc: 'name',
            },
        });

        const pagination = extractPagination(data);
        users.value = pagination.items;
        page.value = pagination.page;
        lastPage.value = Math.max(1, pagination.lastPage);
        total.value = pagination.total;
        perPage.value = pagination.perPage || perPage.value;
    } finally {
        loading.value = false;
    }
}

function goToPage(nextPage) {
    if (nextPage < 1 || nextPage > lastPage.value || nextPage === page.value) return;
    page.value = nextPage;
    loadUsers();
}

function changePerPage() {
    page.value = 1;
    loadUsers();
}

function applyFilters() {
    page.value = 1;
    loadUsers();
}

function resetFilters() {
    search.value = '';
    environmentId.value = '';
    profile.value = '';
    activated.value = '';
    page.value = 1;
    loadUsers();
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        page.value = 1;
        loadUsers();
    }, 300);
}

function duplicateUser(user) {
    closeMenu();
    router.push({
        name: 'users.create',
        query: { duplicate_from: String(user.id) },
    });
}

async function deleteUser(user) {
    closeMenu();

    if (user.id === currentUserId.value) {
        alert('Vous ne pouvez pas supprimer votre propre compte.');
        return;
    }

    if (!confirm(`Supprimer l'utilisateur « ${user.name} » ? Cette action est définitive.`)) {
        return;
    }

    try {
        await api.delete(`/users/${user.id}`);
        if (users.value.length === 1 && page.value > 1) {
            page.value -= 1;
        }
        await loadUsers();
    } catch (err) {
        const payload = err.response?.data;
        const message = payload?.message
            || Object.values(payload?.errors ?? payload?.data ?? {}).flat().join(' ')
            || 'Impossible de supprimer cet utilisateur.';
        alert(Array.isArray(message) ? message[0] : message);
    }
}

onMounted(async () => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('scroll', onWindowScrollOrResize, true);
    window.addEventListener('resize', onWindowScrollOrResize);
    await loadEnvironments();
    await loadUsers();
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('scroll', onWindowScrollOrResize, true);
    window.removeEventListener('resize', onWindowScrollOrResize);
    clearTimeout(searchTimer);
});
</script>

<style scoped>
.users-history {
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
}

.users-history-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.users-history-heading h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
}

.users-history-heading p {
    margin: 0.25rem 0 0;
    font-size: 0.85rem;
    color: #64748b;
}

.users-history-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.users-history-btn,
.users-history-reset,
.users-history-pages button {
    border-radius: 0.55rem;
    font-size: 0.8125rem;
    font-weight: 600;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.users-history-btn,
.users-history-reset,
.users-history-pages button {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    padding: 0.55rem 0.85rem;
}

.users-history-btn:hover,
.users-history-reset:hover,
.users-history-pages button:hover:not(:disabled) {
    background: #f8fafc;
}

.users-history-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10.5rem, 1fr));
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
    border-bottom: 1px solid #f1f5f9;
}

.users-history-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}

.users-history-field-grow {
    grid-column: span 2;
}

.users-history-field span {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
}

.users-history-field input,
.users-history-field select {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.55rem;
    background: #fff;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
    color: #0f172a;
    outline: none;
}

.users-history-field input:focus,
.users-history-field select:focus {
    border-color: #c00000;
    box-shadow: 0 0 0 3px rgb(192 0 0 / 12%);
}

.users-history-reset {
    align-self: end;
}

.users-history-table-wrap {
    overflow-x: auto;
}

.users-history-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 0.875rem;
}

.users-history-table th {
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    white-space: nowrap;
}

.users-history-table td {
    padding: 0.95rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #334155;
}

.users-history-table tbody tr:hover {
    background: #fff8f8;
}

.users-history-identity {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 14rem;
}

.users-history-avatar {
    display: inline-flex;
    height: 2.25rem;
    width: 2.25rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #fef2f2;
    color: #c00000;
    font-size: 0.75rem;
    font-weight: 700;
}

.users-history-name {
    margin: 0;
    font-weight: 650;
    color: #0f172a;
}

.users-history-email {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
}

.users-history-profile {
    margin: 0;
    font-weight: 600;
    color: #0f172a;
}

.users-history-role {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
}

.users-history-chip {
    display: inline-flex;
    max-width: 12rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    border-radius: 999px;
    background: #f1f5f9;
    padding: 0.2rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
}

.users-history-chip.muted {
    background: #f8fafc;
    color: #64748b;
}

.users-history-status {
    display: inline-flex;
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.users-history-status.is-active {
    background: #ecfdf5;
    color: #047857;
}

.users-history-status.is-inactive {
    background: #f1f5f9;
    color: #64748b;
}

.users-history-menu {
    position: relative;
    display: inline-flex;
    justify-content: flex-end;
}

.users-history-menu-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid transparent;
    border-radius: 0.55rem;
    background: transparent;
    color: #64748b;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.users-history-menu-trigger:hover,
.users-history-menu.open .users-history-menu-trigger {
    border-color: #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
}

.users-history-menu-panel {
    position: fixed;
    z-index: 80;
    min-width: 10.5rem;
    padding: 0.35rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.users-history-menu-item {
    display: flex;
    width: 100%;
    align-items: center;
    border: 0;
    border-radius: 0.45rem;
    background: transparent;
    padding: 0.55rem 0.7rem;
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 600;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}

.users-history-menu-item:hover {
    background: #f8fafc;
}

.users-history-menu-item.danger {
    color: #b91c1c;
}

.users-history-menu-item.danger:hover:not(:disabled) {
    background: #fef2f2;
}

.users-history-menu-item:disabled {
    cursor: not-allowed;
    opacity: 0.4;
}

.users-history-empty,
.users-history-empty-cell {
    padding: 2.5rem 1.25rem;
    text-align: center;
    color: #64748b;
}

.users-history-pagination {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.9rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    background: #fafafa;
}

.users-history-pagination > p {
    margin: 0;
    font-size: 0.85rem;
    color: #64748b;
}

.users-history-pages {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.users-history-pages button.active {
    border-color: #c00000;
    background: #c00000;
    color: #fff;
}

.users-history-pages button:disabled {
    cursor: not-allowed;
    opacity: 0.4;
}

.text-right {
    text-align: right;
}

@media (max-width: 900px) {
    .users-history-field-grow {
        grid-column: span 1;
    }
}
</style>
