<?php

namespace Database\Seeders;

use App\Models\MethodologyPage;
use Illuminate\Database\Seeder;

class MethodologyPageSeeder extends Seeder
{
    public function run(): void
    {
        MethodologyPage::query()->updateOrCreate(
            ['slug' => 'definitions-objectifs'],
            [
                'layout' => 'classic',
                'title' => 'LA CARTOGRAPHIE DES RISQUES',
                'introduction' => "Une des principales innovations de l'accord Bâle II par rapport à Bâle I a été non seulement d'exiger l'allocation de fonds propres à la couverture contre les risques opérationnels mais aussi de prôner un dispositif de gestion des risques opérationnels.",
                'sections' => [
                    [
                        'title' => 'I - Définition de la cartographie des risques',
                        'content' => "La cartographie des risques permet de recenser les risques majeurs d'une organisation et de les présenter de façon synthétique sous une forme hiérarchisée. Cette hiérarchisation s'appuie sur les critères suivants :",
                        'subtitle' => null,
                        'items' => [
                            "L'impact potentiel",
                            'La probabilité de survenance',
                            'Le niveau actuel de maitrise de risques',
                        ],
                    ],
                    [
                        'title' => 'II – Objectifs de la cartographie des risques',
                        'content' => "L'établissement d'une cartographie des risques peut être motivé par des objectifs de différentes natures aussi importants les uns que les autres.",
                        'subtitle' => 'Ces objectifs sont les suivants :',
                        'items' => [
                            'Mettre en place un contrôle interne ou un processus de management des risques adéquat ;',
                            "Aider le management dans l'élaboration de son plan stratégique et de sa prise de décision",
                            "Orienter le plan d'audit interne en mettant en lumière les processus au niveau desquels se concentrent les risques majeurs ;",
                            "Veiller à la bonne image de l'organisation ;",
                        ],
                    ],
                ],
                'conclusion' => "La cartographie des risques est un puissant outil de pilotage interne. Ainsi, son élaboration exige une méthodologie minutieuse, ce qui permet une détection systématique des risques majeurs.",
                'body_html' => null,
                'grid_data' => null,
                'is_active' => true,
            ]
        );

        MethodologyPage::query()->updateOrCreate(
            ['slug' => 'preambule'],
            [
                'layout' => 'preambule',
                'title' => 'PRÉAMBULE',
                'introduction' => null,
                'sections' => [],
                'conclusion' => null,
                'body_html' => $this->preambuleHtml(),
                'grid_data' => null,
                'is_active' => true,
            ]
        );

        MethodologyPage::query()->updateOrCreate(
            ['slug' => 'principes'],
            [
                'layout' => 'grid',
                'title' => 'PRINCIPES',
                'introduction' => null,
                'sections' => [],
                'conclusion' => null,
                'body_html' => null,
                'grid_data' => [
                    'columns' => ['PRINCIPES', 'ENONCE DU PRINCIPE', 'EXPLICATIONS'],
                    'rows' => [
                        [
                            'label' => 'Principe 1',
                            'statement' => "Objectivité et rationalité de l'évaluation des risques",
                            'explanation' => 'Un exercice de cette nature expose naturellement les acteurs à un risque d\'erreur de jugement ou d\'appréciation sur l\'analyse du risque. Ce biais peut être lié à des facteurs personnels ou psychologiques (syndrome "alarmiste" ou "paranoïaque") ou à un élément de l\'environnement interne ("conflits internes et considérations crypto-personnelles"). Les fiches d\'évaluation prévoient un champ "Commentaires" permettant à l\'évaluateur (ou groupe d\'évaluateurs) de justifier ses évaluations jugées extrêmes (entre 5 et 6).',
                        ],
                        [
                            'label' => 'Principe 2',
                            'statement' => "Objectif de l'évaluation (évaluation du risque inhérent ou risque brut et de la maturité des contrôles)",
                            'explanation' => "Dans le cadre de cet atelier, il vous est demandé de procéder d'une part à l'évaluation des risques inhérents (risques bruts) c'est-à-dire de faire fi des dispositifs de contrôle en vigueur. L'évaluation du risque inhérent permet d'appréhender de manière globale l'exposition de la filiale sur les différents process cible.",
                        ],
                        [
                            'label' => 'Principe 3',
                            'statement' => "Principe de corrélation de l'évaluation des risques avec les objectifs stratégiques et opérationnels",
                            'explanation' => "Il est demandé à chaque évaluateur de procéder à cette exercice en corrélation avec les objectifs opérationnels de ses processus ou sous processus",
                        ],
                        [
                            'label' => 'Principe 4',
                            'statement' => "Probabilité du risque est différente de la fréquence de survenance de l'incident",
                            'explanation' => "Un risque jamais survenu peut présenter une probabilité de survenance élevée et vice-versa. L'évaluateur devra donc avoir à l'esprit que les facteurs rendant possibles la survenance du risque.",
                        ],
                        [
                            'label' => 'Principe 5',
                            'statement' => 'Evaluation de la gravité',
                            'explanation' => "L'appréciation de la gravité est une estimation très fine des impacts supposés de la survenance du risque sur la réalisation des objectifs de l'organisation. Il conviendra de ne pas surestimer des risques dont les impacts ne seraient que « locaux ». De même, il faudra veiller à ne pas sous estimer des risques dont l'impact est plus général. L'exercice d'évaluation requiert un élargissement de la perspective au-delà de votre simple domaine (direction, cellule, process, activité).",
                        ],
                    ],
                ],
                'is_active' => true,
            ]
        );
    }

    private function preambuleHtml(): string
    {
        return <<<'HTML'
<p>Ce travail s'inscrit dans le cadre de la <span class="mp-red">révision de la cartographie exhaustive des risques du groupe COFINA</span>. Nous vous saurions gré de bien vouloir procéder à l'évaluation des risques sur l'onglet intitulé <strong class="mp-blue">FICHE D'ÉVALUATION</strong>.</p>

<p class="mp-bold">La démarche d'évaluation des risques se déroule en trois (3) étapes :</p>
<p class="mp-green mp-bold">A réaliser par l'évaluateur (en filiale) sous la coordination du Chef de projet Audit Interne</p>

<p><strong>Étape 1 :</strong> Évaluation du risque brut (ou inhérent) en termes de probabilité et de gravité. La criticité est calculée <strong>automatiquement</strong>.</p>
<p><strong>Étape 2 :</strong> Évaluation du dispositif de contrôle (Control rating). La maturité du contrôle est calculée <strong>automatiquement</strong>.</p>
<p><strong>Étape 3 :</strong> Détermination du risque résiduel.</p>

<h2 class="mp-section-title">I) Travaux à réaliser par les personnes ressources</h2>

<h3 class="mp-step-title">Étape 1 — Analyse de l'exposition naturelle (ou risque inhérent) :</h3>
<p>Il s'agit de déterminer la probabilité de survenance et la gravité du risque en fonction de l'environnement interne (organisation, RH, gouvernance, SI, processus, etc.) et externe (réglementaire, concurrence, etc.) de l'organisation. L'évaluation se fait sur une échelle à 6 niveaux selon deux axes, en se référant à la feuille <a href="/cartographie/echelle-pg" class="mp-blue">Echelle P &amp; G</a>.</p>

<h3 class="mp-step-title">Étape 2 — Évaluation des dispositifs de contrôles :</h3>
<p>Il s'agit d'attribuer un niveau de maturité au dispositif de contrôle sur une échelle de 1 à 5, en se référant à la feuille <a href="/cartographie/echelle-controle" class="mp-blue">Echelle de contrôle</a>. L'approche adoptée consiste à déterminer une appréciation <strong>conceptuelle</strong> du contrôle (présence et niveau de pertinence du contrôle) plutôt qu'un audit opérationnel consistant à tester le contrôle. L'efficacité <strong>opérationnelle</strong> sera déterminée ultérieurement (Audit, auto-évaluation) pour alimenter la cartographie avec les données terrain.</p>

<h3 class="mp-step-title">Étape 3 — Analyse de la vulnérabilité réelle ou risque résiduel :</h3>
<p>Le risque résiduel reflète le niveau de criticité subsistant après prise en compte du dispositif de contrôle interne, en supposant que les contrôles sont correctement implémentés. Les données de probabilité et de gravité alimenteront la cartographie des risques sur la matrice de criticité, en se référant à la feuille <a href="/cartographie/matrice-risques" class="mp-blue">Matrice</a> (G = X et P = Y).</p>

<p class="mp-footer">L'équipe projet vous remercie de votre collaboration !!!</p>
HTML;
    }
}
