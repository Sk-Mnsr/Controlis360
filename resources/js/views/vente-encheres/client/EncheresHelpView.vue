<template>
    <article class="ench-help">
        <header class="ench-help-head">
            <p class="ench-help-kicker">Aide</p>
            <h1>Comment fonctionnent les enchères</h1>
            <p>
                Ces explications décrivent le parcours public EnchèreSN : consulter les biens, ouvrir un compte par e-mail, puis enchérir.
            </p>
        </header>

        <nav class="ench-help-toc" aria-label="Sommaire">
            <a v-for="item in sections" :key="item.id" :href="`#${item.id}`">{{ item.title }}</a>
        </nav>

        <section id="consulter" class="ench-help-card">
            <h2>1. Consulter les offres</h2>
            <p>Le catalogue est ouvert à tout le monde. Aucune connexion n’est demandée pour voir les biens.</p>
            <ol>
                <li>Ouvrez <strong>Accueil</strong> ou la liste des enchères.</li>
                <li>Utilisez la recherche, le menu <strong>Enchère</strong> ou les filtres (catégorie, région, prix, statut).</li>
                <li>Chaque carte indique le lot, le lieu, le nombre d’enchères et le temps restant.</li>
                <li>Cliquez sur <strong>Voir les détails</strong> pour la description, les photos et les vidéos.</li>
            </ol>
        </section>

        <section id="compte" class="ench-help-card">
            <h2>2. Ouvrir ou retrouver un compte</h2>
            <p>Le compte client EnchèreSN est séparé des autres espaces. Il se crée et s’ouvre uniquement avec une adresse e-mail.</p>
            <ol>
                <li>Cliquez sur <strong>Se connecter</strong>.</li>
                <li>Saisissez votre adresse e-mail, puis <strong>Continuer</strong>. Un numéro de téléphone n’est pas accepté.</li>
                <li>Un code à 6 chiffres est envoyé à cette adresse. Il reste valable 10 minutes.</li>
                <li>Saisissez le code, puis <strong>Valider le code</strong>.</li>
            </ol>
            <p>Si l’adresse est nouvelle, le compte est créé à cette étape. Si elle existe déjà, le même code rouvre la session.</p>
        </section>

        <section id="encherir" class="ench-help-card">
            <h2>3. Placer une enchère</h2>
            <ol>
                <li>Ouvrez la fiche du bien pendant que l’enchère est encore en cours.</li>
                <li>Connectez-vous si le bouton affiche <strong>Se connecter pour enchérir</strong>.</li>
                <li>Saisissez votre montant en FCFA, puis confirmez.</li>
                <li>Le nombre d’enchères de la fiche est mis à jour.</li>
            </ol>
            <p>Une enchère terminée ne peut plus recevoir d’offre. Le temps restant indique <strong>Terminée</strong>.</p>
        </section>

        <section id="montants" class="ench-help-card">
            <h2>4. Lire les montants</h2>
            <ul>
                <li>Le <strong>prix actuel</strong>, le nombre d’enchères, le temps restant et la <strong>prochaine enchère minimum</strong> sont visibles sur les cartes et sur la fiche.</li>
                <li>Les offres individuelles des autres participants restent privées.</li>
                <li>Une fois connecté, vous voyez uniquement vos propres enchères. L’administration voit l’historique complet.</li>
            </ul>
        </section>

        <section id="cloture" class="ench-help-card">
            <h2>5. Après la clôture</h2>
            <p>Quand la date de fin est passée, le prix actuel et la prochaine enchère minimum deviennent visibles en FCFA. Le compte à rebours indique que l’enchère est terminée, et le bouton pour enchérir est désactivé.</p>
        </section>
    </article>
</template>

<script setup>
const sections = [
    { id: 'consulter', title: 'Consulter les offres' },
    { id: 'compte', title: 'Ouvrir un compte' },
    { id: 'encherir', title: 'Placer une enchère' },
    { id: 'montants', title: 'Lire les montants' },
    { id: 'cloture', title: 'Après la clôture' },
];
</script>

<style scoped>
.ench-help {
    max-width: 860px;
    margin: 0 auto;
    padding: 1.75rem 1.25rem 3rem;
}

.ench-help-kicker {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #2563eb;
}

.ench-help-head h1 {
    margin: 0.35rem 0 0;
    font-size: clamp(1.6rem, 3vw, 2.1rem);
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.03em;
}

.ench-help-head p {
    margin: 0.7rem 0 0;
    color: #475569;
    line-height: 1.55;
}

.ench-help-toc {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1.25rem 0 0.5rem;
}

.ench-help-toc a {
    border-radius: 999px;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.4rem 0.75rem;
}

.ench-help-toc a:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.ench-help-card {
    margin-top: 1rem;
    background: #fff;
    border: 1px solid #e8eef5;
    border-radius: 1rem;
    padding: 1.15rem 1.2rem 1.2rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.ench-help-card h2 {
    margin: 0;
    font-size: 1.15rem;
    color: #0f172a;
}

.ench-help-card p,
.ench-help-card li {
    color: #334155;
    line-height: 1.55;
}

.ench-help-card ol,
.ench-help-card ul {
    margin: 0.75rem 0 0;
    padding-left: 1.2rem;
}

.ench-help-card li + li {
    margin-top: 0.35rem;
}
</style>
