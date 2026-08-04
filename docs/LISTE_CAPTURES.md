# Liste des captures d’écran — Manuel Controlis360

Enregistrer chaque capture dans `docs/captures/` en **PNG**, avec le **nom exact** du tableau (ex. `01-connexion.png`).  
Des **placeholders SVG** du même nom existent déjà : dès qu’un PNG est déposé, le HTML PDF l’utilise automatiquement (le SVG reste en secours).

Format recommandé : **PNG**, fenêtre ~1280×800 (ou plein écran), zoom 100 %.

**Comptes de démo conseillés :** Agent contrôle, Responsable contrôle, Responsable entité, Audit / Contrôle, Régulateur.

| # | Fichier | Écran à capturer | Compte | Notes |
|---|---------|------------------|--------|-------|
| 01 | `01-connexion.png` | Page **Connexion** | — | Sans données sensibles dans les champs |
| 02 | `02-changer-mot-de-passe.png` | **Changer le mot de passe** | Nouveau compte | Masquer les mots de passe saisis |
| 03 | `03-portail.png` | Portail **Controlis360** (modules) | Utilisateur métier | Cartes des modules accessibles |
| 04 | `04-sidebar-cartographie.png` | Module Cartographie — barre latérale complète | Contrôle | Menus Méthodologie / Saisie / Départements |
| 05 | `05-cartographie-heatmaps.png` | **Cartographie** consolidée (heatmaps) | Contrôle | Montrer filtre filiale, onglets bruts/résiduels, heatmap P×G, détail des risques |
| 06 | `06-methodologie-matrice.png` | Page **Méthodologie** (Préambule, Matrice…) | Contrôle | Montrer le menu Méthodologie + contenu (ex. 3 étapes du Préambule) |
| 07 | `07-saisie-nouvelle-ligne.png` | **Saisie des risques** / Nouvelle ligne | Agent contrôle | Formulaire visible |
| 08 | `08-analyse-entite.png` | Analyse département/agence + table | Responsable contrôle | Onglets Soumissions visibles si possible |
| 09 | `09-plus-gros-risques.png` | **Plus Gros Risques** | Contrôle | |
| 10 | `10-sidebar-suivi-reco.png` | Module Suivi des reco — sidebar | Audit | Dashboard / Missions / Paramétrage |
| 11 | `11-dashboard-audit.png` | **Tableau de bord** KPI + graphiques | Audit | Layout large (6 KPI) si possible |
| 12 | `12-nouvelle-mission.png` | Formulaire **Nouvelle mission** (étapes 1–2) | Audit | |
| 13 | `13-modal-missionnaires.png` | Modal **Missionnaires** (rôle Responsable/Membre) | Audit | Champ « Rôle dans la mission » visible |
| 14 | `14-detail-mission.png` | **Détail mission** + liste recos | Audit | Bouton + Reco visible |
| 15 | `15-nouvelle-recommandation.png` | Formulaire **Nouvelle recommandation** | Audit | |
| 16 | `16-plan-action-metier.png` | Détail reco côté métier (Action / Affecter) | Responsable entité | |
| 18 | `18-parametrage.png` | **Paramétrage** types / couleurs | Audit / Contrôle | |

## Progression

- [ ] 01 Connexion  
- [ ] 02 Changer mot de passe  
- [ ] 03 Portail  
- [ ] 04 Sidebar cartographie  
- [ ] 05 Heatmaps  
- [ ] 06 Méthodologie  
- [ ] 07 Saisie  
- [ ] 08 Analyse entité  
- [ ] 09 Plus gros risques  
- [ ] 10 Sidebar suivi reco  
- [ ] 11 Dashboard audit  
- [ ] 12 Nouvelle mission  
- [ ] 13 Missionnaires  
- [ ] 14 Détail mission  
- [ ] 15 Nouvelle reco  
- [ ] 16 Plan d’action métier  
- [ ] 18 Paramétrage  

## Conseils de prise de vue

1. Utiliser des **données fictives** (pas de vrais e-mails clients / mots de passe).
2. Éviter les barres d’URL / favoris si possible (capture fenêtre contenu) — ou recadrer.
3. Pour les modales : capturer la **modale + un peu de fond** pour le contexte.
4. Une fois les PNG déposés dans `docs/captures/`, ouvrir `manuel-pdf.html` dans le navigateur → **Imprimer → Enregistrer au format PDF**.
