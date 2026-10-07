<template>
    <div v-if="clientAuth.isLoggedIn" class="ench-account">
        <aside class="ench-account-nav">
            <h2>Mon espace</h2>
            <nav>
                <button
                    v-for="item in menu"
                    :key="item.id"
                    type="button"
                    class="ench-account-link"
                    :class="{ active: active === item.id }"
                    @click="active = item.id"
                >
                    {{ item.label }}
                    <span v-if="item.count" class="ench-account-count">{{ item.count }}</span>
                </button>
            </nav>
            <button type="button" class="ench-account-logout" @click="logout">
                Se déconnecter
            </button>
        </aside>

        <section class="ench-account-content">
            <p v-if="loading" class="ench-account-muted">Chargement de votre espace…</p>
            <template v-else>
                <header class="ench-account-head">
                    <div>
                        <h1>{{ activeLabel }}</h1>
                        <p class="ench-account-intro">
                            Bonjour <strong>{{ clientName }}</strong>.
                            Compte vérifié : <strong>{{ clientAuth.client?.email }}</strong>.
                        </p>
                    </div>
                </header>

                <template v-if="active === 'dashboard'">
                    <div class="ench-account-stats">
                        <article>
                            <p>Enchères suivies</p>
                            <strong>{{ participations.length }}</strong>
                        </article>
                        <article>
                            <p>En tête</p>
                            <strong>{{ winning.length }}</strong>
                        </article>
                        <article>
                            <p>Dépassées</p>
                            <strong>{{ lost.length }}</strong>
                        </article>
                    </div>

                    <div v-if="dashboardNotes.length" class="ench-account-notes">
                        <p v-for="note in dashboardNotes" :key="note">{{ note }}</p>
                    </div>
                    <p v-else class="ench-account-empty">
                        Vous n'avez pas encore enchéri.
                        <RouterLink :to="{ name: 'vente-encheres.client.list' }">Voir les enchères</RouterLink>
                    </p>

                    <div v-if="participations.length" class="ench-account-block">
                        <h2>Activité récente</h2>
                        <BidRow v-for="row in participations.slice(0, 4)" :key="row.key" :row="row" />
                    </div>
                </template>

                <template v-else-if="active === 'bids'">
                    <p v-if="!participations.length" class="ench-account-empty">
                        Aucune enchère pour ce compte.
                        <RouterLink :to="{ name: 'vente-encheres.client.list' }">Parcourir le catalogue</RouterLink>
                    </p>
                    <BidRow v-for="row in participations" :key="row.key" :row="row" />
                </template>

                <template v-else-if="active === 'won'">
                    <p v-if="!winning.length" class="ench-account-empty">Aucune offre gagnante pour le moment.</p>
                    <BidRow v-for="row in winning" :key="row.key" :row="row" />
                </template>

                <template v-else-if="active === 'lost'">
                    <p v-if="!lost.length" class="ench-account-empty">Aucune enchère dépassée.</p>
                    <BidRow v-for="row in lost" :key="row.key" :row="row" />
                </template>

                <template v-else-if="active === 'favorites'">
                    <p v-if="!favoriteAuctions.length" class="ench-account-empty">
                        Le cœur sur une carte enregistre le lot ici, sur cet appareil.
                    </p>
                    <article v-for="auction in favoriteAuctions" :key="auction.id" class="ench-bid-row">
                        <div>
                            <p class="ench-bid-lot">{{ lotLabel(auction) }}</p>
                            <RouterLink :to="detailTo(auction)">{{ auction.title }}</RouterLink>
                            <p class="ench-account-muted">{{ auction.location }}</p>
                        </div>
                        <div>
                            <p class="ench-account-muted">Prix actuel</p>
                            <strong>{{ formatFcfa(auction.current_price) }}</strong>
                        </div>
                    </article>
                </template>

                <template v-else-if="active === 'alerts'">
                    <p class="ench-account-muted">
                        Ces alertes concernent uniquement vos enchères. Les offres des autres participants ne sont pas affichées.
                    </p>
                    <p v-if="!alertNotes.length" class="ench-account-empty">Aucune alerte. Rien ne se termine dans les 48 prochaines heures.</p>
                    <div v-else class="ench-account-notes">
                        <p v-for="note in alertNotes" :key="note">{{ note }}</p>
                    </div>
                </template>

                <template v-else-if="active === 'payments'">
                    <p class="ench-account-muted">
                        Le paiement en ligne n'est pas ouvert. Après attribution, EnchèreSN vous contacte à
                        {{ clientAuth.client?.email }} pour le règlement du montant retenu.
                    </p>
                    <p v-if="!wonClosed.length" class="ench-account-empty">Aucun lot remporté en attente de règlement.</p>
                    <article v-for="row in wonClosed" :key="row.key" class="ench-bid-row">
                        <div>
                            <p class="ench-bid-lot">{{ lotLabel(row.auction) }}</p>
                            <RouterLink :to="detailTo(row.auction)">{{ row.auction.title }}</RouterLink>
                            <p class="ench-account-muted">En attente de contact</p>
                        </div>
                        <div>
                            <p class="ench-account-muted">Montant retenu</p>
                            <strong>{{ formatFcfa(row.amount) }}</strong>
                        </div>
                    </article>
                </template>

                <template v-else-if="active === 'documents'">
                    <p class="ench-account-muted">
                        Les pièces d'un lot remporté sont envoyées sur {{ clientAuth.client?.email }}. Elles ne sont pas encore déposées dans cet espace.
                    </p>
                    <p v-if="!wonClosed.length" class="ench-account-empty">Aucun document disponible.</p>
                    <ul v-else class="ench-account-list">
                        <li v-for="row in wonClosed" :key="row.key">
                            Bon d'attribution à venir pour {{ lotLabel(row.auction) }} — {{ row.auction.title }}.
                        </li>
                    </ul>
                </template>

                <template v-else-if="active === 'profile'">
                    <dl class="ench-profile">
                        <div>
                            <dt>Nom</dt>
                            <dd>{{ clientName }}</dd>
                        </div>
                        <div>
                            <dt>E-mail</dt>
                            <dd>{{ clientAuth.client?.email }}</dd>
                        </div>
                        <div>
                            <dt>Compte</dt>
                            <dd>Vérifié par code e-mail</dd>
                        </div>
                    </dl>
                </template>

                <template v-else-if="active === 'settings'">
                    <label class="ench-setting">
                        <input v-model="alertsEnabled" type="checkbox" @change="saveAlerts" />
                        <span>Afficher dans cet espace un rappel quand une de mes enchères se termine dans moins de 48 heures.</span>
                    </label>
                    <p class="ench-account-muted">
                        Le code de connexion est envoyé à {{ clientAuth.client?.email }}. La déconnexion se fait depuis le menu de gauche.
                    </p>
                </template>
            </template>
        </section>
    </div>
    <div v-else class="ench-account-guest">
        <h1>Mon compte</h1>
        <p>Connectez-vous avec votre adresse e-mail pour suivre vos enchères.</p>
        <button type="button" class="ench-account-login" @click="clientAuth.show()">Se connecter</button>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { formatFcfa } from '../../../config/encheres-mock-data';
import { useEncheresFavorites } from '../../../composables/useEncheresFavorites';
import { fetchAuctions } from '../../../services/encheresApi';
import { useEncheresClientAuthStore } from '../../../stores/encheresClientAuth';

const clientAuth = useEncheresClientAuthStore();
const favorites = useEncheresFavorites();
const active = ref('dashboard');
const loading = ref(true);
const auctions = ref([]);
const alertsEnabled = ref(true);

const clientName = computed(() => clientAuth.client?.name || 'Client');

const BidRow = defineComponent({
    name: 'BidRow',
    props: {
        row: { type: Object, required: true },
    },
    setup(props) {
        return () => h('article', { class: 'ench-bid-row' }, [
            h('div', [
                h('p', { class: 'ench-bid-lot' }, lotLabel(props.row.auction)),
                h(RouterLink, { to: detailTo(props.row.auction) }, () => props.row.auction.title),
                h('p', { class: 'ench-account-muted' }, props.row.auction.location || ''),
            ]),
            h('div', [
                h('p', { class: 'ench-account-muted' }, 'Mon offre'),
                h('strong', formatFcfa(props.row.amount)),
            ]),
            h('span', { class: ['ench-bid-badge', badgeClass(props.row)] }, badgeLabel(props.row)),
        ]);
    },
});

const participations = computed(() => latestOwnBids(auctions.value));
const winning = computed(() => participations.value.filter((row) => isWinning(row.bid)));
const lost = computed(() => participations.value.filter((row) => !isWinning(row.bid)));
const wonClosed = computed(() => winning.value.filter((row) => !isLive(row.auction)));
const favoriteAuctions = computed(() => auctions.value.filter((auction) => favorites.isFavorite(auction.id)));

const menu = computed(() => [
    { id: 'dashboard', label: 'Tableau de bord' },
    { id: 'bids', label: 'Mes enchères', count: participations.value.length },
    { id: 'won', label: 'Mes enchères gagnées', count: winning.value.length },
    { id: 'lost', label: 'Mes enchères perdues', count: lost.value.length },
    { id: 'favorites', label: 'Mes favoris', count: favoriteAuctions.value.length },
    { id: 'alerts', label: 'Mes alertes', count: alertNotes.value.length },
    { id: 'payments', label: 'Mes paiements', count: wonClosed.value.length },
    { id: 'documents', label: 'Mes documents' },
    { id: 'profile', label: 'Mon profil' },
    { id: 'settings', label: 'Paramètres' },
]);

const activeLabel = computed(() => menu.value.find((item) => item.id === active.value)?.label ?? 'Tableau de bord');

const alertNotes = computed(() => {
    if (!alertsEnabled.value) {
        return [];
    }
    return participations.value
        .filter((row) => isLive(row.auction) && hoursLeft(row.auction) <= 48)
        .map((row) => {
            const state = isWinning(row.bid) ? 'Votre offre est en tête' : 'Votre offre est dépassée';
            return `${state} sur ${lotLabel(row.auction)} — ${row.auction.title}. Fin dans ${hoursLabel(row.auction)}.`;
        });
});

const dashboardNotes = computed(() => {
    const notes = winning.value
        .filter((row) => isLive(row.auction))
        .map((row) => `Votre offre est actuellement gagnante sur ${lotLabel(row.auction)} — ${row.auction.title}.`);
    return [...notes, ...alertNotes.value.filter((note) => !notes.some((item) => item.includes(note.slice(0, 24))))];
});

onMounted(async () => {
    alertsEnabled.value = readAlerts(clientAuth.client?.email);
    if (!clientAuth.isLoggedIn) {
        loading.value = false;
        clientAuth.show();
        return;
    }
    try {
        auctions.value = await fetchAuctions();
    } finally {
        loading.value = false;
    }
});

async function logout() {
    await clientAuth.logout();
    clientAuth.show();
}

function saveAlerts() {
    localStorage.setItem(alertsKey(clientAuth.client?.email), alertsEnabled.value ? '1' : '0');
}

function alertsKey(email) {
    return `encheresnAlerts:${String(email || '').toLowerCase()}`;
}

function readAlerts(email) {
    return localStorage.getItem(alertsKey(email)) !== '0';
}

function ownsBid(bid) {
    const email = String(clientAuth.client?.email || '').trim().toLowerCase();
    const name = String(clientAuth.client?.name || '').trim().toLowerCase();
    const ref = String(bid?.user_ref || '').trim().toLowerCase();
    const bidEmail = String(bid?.email || '').trim().toLowerCase();
    if (email && (bidEmail === email || ref === email)) {
        return true;
    }
    return Boolean(name) && ref === name;
}

function latestOwnBids(list) {
    const byAuction = new Map();
    list.forEach((auction) => {
        (auction.bids || []).forEach((bid) => {
            if (!ownsBid(bid)) {
                return;
            }
            const current = byAuction.get(auction.id);
            const at = new Date(bid.at || 0).getTime();
            if (!current || at >= current.at) {
                byAuction.set(auction.id, {
                    key: `${auction.id}-${bid.id}`,
                    auction,
                    bid,
                    amount: bid.amount,
                    at,
                });
            }
        });
    });
    return [...byAuction.values()].sort((a, b) => b.at - a.at);
}

function isWinning(bid) {
    return String(bid?.status || '').toLowerCase().includes('gagn');
}

function isLive(auction) {
    return new Date(auction?.ends_at).getTime() > Date.now();
}

function hoursLeft(auction) {
    return (new Date(auction.ends_at).getTime() - Date.now()) / 36e5;
}

function hoursLabel(auction) {
    const hours = Math.max(0, Math.floor(hoursLeft(auction)));
    if (hours < 1) {
        return 'moins d’une heure';
    }
    return `${hours} h`;
}

function lotLabel(auction) {
    const lot = String(auction?.lot || auction?.id || '').trim();
    return /^lot\b/i.test(lot) ? lot : `LOT #${lot}`;
}

function detailTo(auction) {
    return { name: 'vente-encheres.client.detail', params: { id: auction.id } };
}

function badgeLabel(row) {
    if (isWinning(row.bid) && isLive(row.auction)) {
        return 'En tête';
    }
    if (isWinning(row.bid)) {
        return 'Remportée';
    }
    return 'Dépassée';
}

function badgeClass(row) {
    if (isWinning(row.bid) && isLive(row.auction)) {
        return 'is-live';
    }
    if (isWinning(row.bid)) {
        return 'is-won';
    }
    return 'is-lost';
}
</script>

<style scoped>
.ench-account-guest {
    max-width: 36rem;
    margin: 2rem auto;
    padding: 0 1rem;
    color: #14213d;
}

.ench-account-guest h1 {
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
}

.ench-account-login,
.ench-account-logout {
    border-radius: 0.65rem;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
}

.ench-account-login {
    margin-top: 1rem;
    border: 0;
    background: #ffb000;
    color: #071b41;
    padding: 0.7rem 1rem;
}

.ench-account {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: 240px minmax(0, 1fr);
}

.ench-account-nav,
.ench-account-content {
    background: #fff;
    border: 1px solid #e8eef5;
    border-radius: 0.9rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.ench-account-nav {
    padding: 1rem;
    align-self: start;
}

.ench-account-nav h2 {
    margin: 0 0 0.75rem;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}

.ench-account-nav nav {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.ench-account-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    text-align: left;
    border: 0;
    background: none;
    padding: 0.5rem 0.55rem;
    border-radius: 0.45rem;
    font-size: 0.84rem;
    font-weight: 700;
    color: #14213d;
    cursor: pointer;
}

.ench-account-link.active,
.ench-account-link:hover {
    background: #eaf3ff;
    color: #0057d9;
}

.ench-account-count {
    min-width: 1.3rem;
    border-radius: 999px;
    background: #071b41;
    color: #fff;
    font-size: 0.68rem;
    text-align: center;
    padding: 0.1rem 0.35rem;
}

.ench-account-link.active .ench-account-count {
    background: #0057d9;
}

.ench-account-logout {
    margin-top: 1rem;
    width: 100%;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #14213d;
    padding: 0.6rem 0.75rem;
}

.ench-account-content {
    padding: 1.25rem;
    min-width: 0;
}

.ench-account-content h1 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 800;
    color: #071b41;
}

.ench-account-intro,
.ench-account-muted,
.ench-account-empty {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.5;
}

.ench-account-intro {
    margin: 0.45rem 0 1rem;
}

.ench-account-empty a,
.ench-account-content :deep(a) {
    color: #0057d9;
    font-weight: 800;
}

.ench-account-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.ench-account-stats article {
    background: #eaf3ff;
    border-radius: 0.75rem;
    padding: 0.8rem 0.9rem;
}

.ench-account-stats p {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0057d9;
}

.ench-account-stats strong {
    display: block;
    margin-top: 0.25rem;
    font-size: 1.45rem;
    color: #071b41;
}

.ench-account-notes {
    display: grid;
    gap: 0.55rem;
    margin-bottom: 1rem;
}

.ench-account-notes p,
.ench-account-list {
    margin: 0;
    background: #fff8e8;
    border-radius: 0.7rem;
    padding: 0.75rem 0.85rem;
    color: #14213d;
    font-size: 0.9rem;
}

.ench-account-list {
    padding-left: 1.4rem;
}

.ench-account-block h2 {
    margin: 0.4rem 0 0.7rem;
    font-size: 1rem;
    color: #071b41;
}

.ench-account-content :deep(.ench-bid-row) {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) auto auto;
    gap: 0.75rem;
    align-items: center;
    border: 1px solid #e8eef5;
    border-radius: 0.8rem;
    padding: 0.8rem 0.9rem;
    margin-bottom: 0.65rem;
}

.ench-account-content :deep(.ench-bid-lot),
.ench-account-content :deep(.ench-account-muted) {
    margin: 0;
    color: #64748b;
    font-size: 0.75rem;
}

.ench-account-content :deep(.ench-bid-row a) {
    display: block;
    color: #071b41;
    font-weight: 800;
    text-decoration: none;
}

.ench-account-content :deep(.ench-bid-row strong) {
    color: #071b41;
}

.ench-account-content :deep(.ench-bid-badge) {
    justify-self: end;
    border-radius: 999px;
    padding: 0.25rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 800;
}

.ench-account-content :deep(.is-live) {
    background: #eaf3ff;
    color: #0057d9;
}

.ench-account-content :deep(.is-won) {
    background: #dcfce7;
    color: #15803d;
}

.ench-account-content :deep(.is-lost) {
    background: #f1f5f9;
    color: #64748b;
}

.ench-profile {
    display: grid;
    gap: 0.75rem;
    margin: 0;
}

.ench-profile div {
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e8eef5;
}

.ench-profile dt {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
}

.ench-profile dd {
    margin: 0.2rem 0 0;
    font-weight: 800;
    color: #071b41;
}

.ench-setting {
    display: flex;
    gap: 0.65rem;
    align-items: flex-start;
    margin-bottom: 0.8rem;
    color: #14213d;
    font-size: 0.92rem;
    line-height: 1.45;
}

@media (max-width: 800px) {
    .ench-account {
        grid-template-columns: 1fr;
    }

    .ench-account-nav nav {
        flex-direction: row;
        overflow-x: auto;
        gap: 0.4rem;
        padding-bottom: 0.35rem;
    }

    .ench-account-link {
        flex: 0 0 auto;
        background: #f5f7fa;
    }

    .ench-account-stats,
    .ench-account-content :deep(.ench-bid-row) {
        grid-template-columns: 1fr;
    }
}
</style>
