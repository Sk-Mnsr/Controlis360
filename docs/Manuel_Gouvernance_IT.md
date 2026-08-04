# Manuel d’utilisation — Module Gouvernance IT

**Application :** Controlis360  
**Module :** Gouvernance IT / GovStrat IT-R  
**Public :** Agent IT, Responsable IT, Responsable Régional, Administrateurs  

---

## 1. Introduction

Le module **Gouvernance IT** permet de suivre et piloter les activités IT de chaque filiale :

- projets et chantiers en cours ;
- incidents et points d’attention ;
- échanges entre équipes IT et Responsable Régional ;
- pièces jointes et validation avant envoi régional.

L’accès se fait via le portail Controlis360 → module **Gouvernance IT** → **GovStrat IT-R**.

---

## 2. Profils et droits d’accès

| Profil | Code | Rôle métier | Accès principal |
|--------|------|-------------|-----------------|
| Agent IT | `agent_it` | AGIT | CENTRE SUPPORT, SYSTEMES ET RESEAUX, BASE DE DONNEES |
| Responsable IT | `responsable_it` | RESPIT | Idem Agent IT + validation des lignes Agent |
| Responsable Régional | `responsable_regional` | RESPREG | Task ACTIVITY IT (vue consolidée par filiale) |
| Super Admin / Admin | — | — | Tous les modules |

### Règles importantes

- **Agent IT** : peut **créer** uniquement des lignes dans **Points d’Attention** ; peut **modifier / Save / Soumettre / Send** les lignes dont il est **Owner** (nom exact).
- **Responsable IT** : gère toutes les sections ; valide les soumissions Agent IT ; peut envoyer au Régional.
- **Responsable Régional** : consulte les lignes envoyées / points de sa filiale ; peut discuter (chat) et joindre des fichiers.

---

## 3. Accéder à GovStrat IT-R

1. Se connecter à Controlis360.
2. Ouvrir le module **Gouvernance IT**.
3. Cliquer sur **GovStrat IT-R**.

Les sous-modules s’affichent sous forme d’**engrenages** :

| Engrenage | Visible pour |
|-----------|----------------|
| Task ACTIVITY IT | Responsable Régional, Admin |
| CENTRE SUPPORT | Agent IT, Responsable IT, Admin |
| SYSTEMES ET RESEAUX | Agent IT, Responsable IT, Admin |
| BASE DE DONNEES | Agent IT, Responsable IT, Admin |

**Comportement :** un clic sur un engrenage ouvre le formulaire **juste en dessous**, sans masquer les engrenages. Un second clic sur le même engrenage le referme.

---

## 4. En-tête filiale / équipe

Sur CENTRE SUPPORT, SYSTEMES ET RESEAUX et BASE DE DONNEES, l’en-tête affiche :

| Champ | Contenu |
|-------|---------|
| **Filiale** | Environnement / pays de l’utilisateur |
| **Responsable** | Responsable(s) IT de la filiale |
| **Team** | Agent(s) IT de la filiale |

Le bouton **+** rouge (en haut à droite de l’en-tête) crée un **nouvel ensemble**.

Sur **Task ACTIVITY IT**, la filiale est un **menu déroulant** parmi les environnements affectés au Responsable Régional.

---

## 5. Ensembles

Un **ensemble** regroupe les tableaux d’une période (libellé automatique du type « Ensemble du JJ/MM/AAAA HH:MM »).

| Action | Comment faire |
|--------|----------------|
| Créer | Cliquer sur le **+** rouge de l’en-tête |
| Supprimer | Bouton **Supprimer l’ensemble ×** (supprime aussi toutes les lignes) |

---

## 6. Sections (tableaux)

Chaque ensemble contient les sections suivantes :

1. **Projets en cours**
2. **Chantiers en cours**
3. **Chantier Système d'Information Flexcube (SI)**
4. **INCIDENTS**
5. **Points d'Attention**

Le **+** bleu dans le bandeau rouge d’une section ajoute une **ligne** dans cette section.

> **Agent IT** : le + de création n’est actif que pour **Points d’Attention**.

---

## 7. Colonnes d’une ligne

| Colonne | Description |
|---------|-------------|
| **N°** | Numéro automatique |
| **Titre** (nom de la section) | Libellé de l’activité |
| **Impact \*** | Texte **obligatoire** pour Save / Soumettre / Send |
| **Owner** | Responsable de la ligne (liste des membres IT). Si vous êtes Owner, la case s’affiche en **jaune moutarde** |
| **Priorité** | P1 / P2 / P3 |
| **Statut** | OPEN (rouge) ou CLOSE (vert). Si une **FINISH DATE** est renseignée, le statut passe automatiquement à **CLOSE** |
| **DATE DE LIVRAISON** | Échéance prévue |
| **START DATE (EFFECTIVE)** | Début réel |
| **FINISH DATE (EFFECTIVE)** | Fin réelle |
| **LEAD TIME** | Calculé automatiquement (jours entre START et FINISH) |
| **Commentaire** | Champ texte de la ligne (aperçu + « Voir plus ») — distinct du chat |
| **Actions** | Chat, pièces jointes, édition, suppression, soumission / validation / envoi |

---

## 8. Actions sur une ligne

### 8.1 Modifier / Enregistrer

1. Cliquer sur l’icône **crayon**.
2. Renseigner les champs (dont **Impact** obligatoire).
3. Cliquer sur **Save**.

### 8.2 Supprimer

Icône **corbeille** rouge (impossible après envoi au Régional).

### 8.3 Circuit de validation (hors Points d’Attention)

```
Agent IT  →  Save  →  Soumettre  →  Responsable IT (Valider)  →  Send  →  Responsable Régional
```

| Étape | Qui | Bouton | Effet |
|-------|-----|--------|-------|
| 1 | Agent IT (Owner) | **Soumettre** | Statut « En attente » (ligne ambre) |
| 2 | Responsable IT | **Valider** | Ligne validée |
| 3 | Agent (après validation) ou Responsable IT | **Send** | Envoi au Responsable Régional → badge **Envoyé** |

**Exception — Points d’Attention :** pas de validation. L’Agent IT peut faire **Send** directement.

> Toute nouvelle modification par l’Agent IT **annule** une validation existante : il faut re-soumettre.

### 8.4 Discussion (chat)

Icône **bulle** dans Actions :

- fil de messages **datés** sur toute la ligne ;
- participants : Agent IT, Responsable IT, Responsable Régional ;
- **différent** du champ **Commentaire** de la colonne.

### 8.5 Pièces jointes

Icône **trombone** dans Actions :

1. Cliquer sur **+** en haut du modal pour ajouter autant de jointures que nécessaire.
2. Pour chaque jointure :
   - **Nom** : libellé de la pièce ;
   - **Pièce jointe** : fichier (max 10 Mo).
3. Cliquer sur **Joindre**.
4. Ensuite : **Voir** (PDF / images), **Télécharger**, ou **×** pour supprimer.

---

## 9. Task ACTIVITY IT (Responsable Régional)

1. Sélectionner la **filiale**.
2. Consulter les lignes par origine (mêmes sections).
3. Colonnes utiles supplémentaires : **Origine** (CENTRE SUPPORT / SYSTEMES…), **Motif** (P1, échéance dépassée, Envoyé…).
4. Actions disponibles : **chat** et **pièces jointes**.

Les lignes P1, échéances OPEN dépassées ou envoyées sont mises en évidence.

---

## 10. Parcours types

### 10.1 Agent IT — Point d’Attention

1. Ouvrir CENTRE SUPPORT (ou autre module ops).
2. Créer / ouvrir un ensemble.
3. Dans **Points d’Attention**, cliquer **+**.
4. Renseigner titre, **Impact**, Owner (soi-même), priorité, dates.
5. **Save** puis **Send**.

### 10.2 Agent IT — Projet / Chantier / Incident (Owner)

1. Le Responsable IT crée la ligne et vous met en Owner (case jaune à la connexion).
2. Vous modifiez → **Save**.
3. **Soumettre** → attendre validation.
4. Après **Valider** par le Responsable IT → **Send**.

### 10.3 Responsable IT — Validation et envoi

1. Repérer les lignes ambre « En attente ».
2. Contrôler le contenu, le chat et les PJ.
3. **Valider** puis **Send** (ou laisser l’Agent envoyer après validation).

### 10.4 Responsable Régional — Suivi

1. Ouvrir **Task ACTIVITY IT**.
2. Choisir la filiale.
3. Suivre les motifs, discuter via le chat, ajouter des PJ si besoin.

---

## 11. Codes couleurs utiles

| Élément | Signification |
|---------|----------------|
| Case Owner **jaune moutarde** | Vous êtes Owner de la ligne |
| Statut **OPEN** rouge | Ouvert |
| Statut **CLOSE** vert | Clos |
| Ligne fond **ambre** + « En attente » | Soumise, en attente de validation Responsable IT |
| Badge **Envoyé** | Transmise au Responsable Régional (ligne verrouillée en édition) |
| Impact **\*** | Champ obligatoire |

---

## 12. FAQ / Dépannage

**Je ne vois pas CENTRE SUPPORT / SYSTEMES / BASE DE DONNEES**  
→ Votre profil est probablement Responsable Régional : utilisez Task ACTIVITY IT.

**Je ne vois pas Task ACTIVITY IT**  
→ Réservé au Responsable Régional (et Admin).

**Je ne peux pas créer de ligne hors Points d’Attention**  
→ Normal pour un Agent IT. Demandez au Responsable IT de créer la ligne et de vous définir comme Owner.

**Save / Send refuse**  
→ Vérifiez que **Impact** est renseigné.

**Send refusé pour un Agent hors Points d’Attention**  
→ La ligne doit d’abord être **validée** par le Responsable IT.

**Ma case Owner n’est pas jaune**  
→ Le nom du compte doit correspondre **exactement** au Owner de la ligne (majuscules/minuscules ignorées, orthographe identique).

**Impossible de modifier une ligne**  
→ Elle est peut-être déjà **Envoyée** (verrouillée). Utilisez le chat / les PJ pour continuer l’échange.

---

## 13. Glossaire

| Terme | Définition |
|-------|------------|
| **Ensemble** | Conteneur daté regroupant tous les tableaux d’un module |
| **Owner** | Personne responsable de la ligne |
| **Impact** | Description de l’impact métier / technique (obligatoire) |
| **Commentaire** | Note libre sur la ligne |
| **Chat / Discussion** | Échanges datés multi-profils sur la ligne |
| **Soumettre** | Demande de validation Responsable IT |
| **Valider** | Approbation Responsable IT |
| **Send** | Envoi au Responsable Régional |
| **Lead Time** | Durée en jours entre START et FINISH DATE |

---

*Document généré pour Controlis360 — Module Gouvernance IT / GovStrat IT-R.*
