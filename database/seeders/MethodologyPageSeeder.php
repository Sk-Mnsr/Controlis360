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
                'introduction' => "Avec les innovations apportées par l'accord de Bâle II, la gestion des risques opérationnels a pris une dimension nouvelle au sein des établissements financiers. La cartographie des risques s'inscrit dans cette démarche de maîtrise et de pilotage des risques inhérents à l'activité.",
                'sections' => [
                    [
                        'title' => 'I - Définition de la cartographie des risques',
                        'content' => "La cartographie des risques est un outil de management qui permet d'identifier et de présenter de manière hiérarchisée les principaux risques auxquels est confrontée l'organisation. Cette hiérarchisation s'appuie notamment sur les critères suivants :",
                        'subtitle' => null,
                        'items' => [
                            "L'impact potentiel",
                            'La probabilité de survenance',
                            'Le niveau actuel de maîtrise des risques',
                        ],
                    ],
                    [
                        'title' => 'II - Objectifs de la cartographie des risques',
                        'content' => "La cartographie des risques vise à fournir une vision consolidée et partagée des expositions aux risques de l'organisation.",
                        'subtitle' => 'Ces objectifs sont les suivants :',
                        'items' => [
                            "Mettre en place des processus adéquats de contrôle interne ou de gestion des risques",
                            "Aider la direction dans la planification stratégique et la prise de décision",
                            "Guider le plan d'audit interne en mettant en évidence les principales zones de risques",
                            "Contribuer à préserver l'image de l'organisation",
                        ],
                    ],
                ],
                'conclusion' => "La cartographie des risques constitue ainsi un outil de management interne puissant, dont la fiabilité repose sur une méthodologie rigoureuse et une mise à jour régulière.",
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
                            'explanation' => "Un exercice de cette nature expose naturellement les acteurs à un risque d'erreur de jugement ou d'appréciation sur l'analyse du risque. Ce biais peut être lié à des facteurs personnels ou psychologiques (syndrome « alarmiste » ou « paranoïaque ») ou à un élément de l'environnement interne (« conflits internes et considérations crypto-personnelles »). Les fiches d'évaluation prévoient un champ « Commentaires » permettant à l'évaluateur (ou groupe d'évaluateurs) de justifier ses évaluations jugées extrêmes (entre 5 et 6).",
                        ],
                        [
                            'label' => 'Principe 2',
                            'statement' => "Objectif de l'évaluation (évaluation du risque inhérent ou risque brut et de la maturité des contrôles)",
                            'explanation' => "Dans le cadre de cet atelier, il vous est demandé de procéder d'une part à l'évaluation des risques inhérents (risques bruts), c'est-à-dire de faire fi des dispositifs de contrôle en vigueur. L'évaluation du risque inhérent permet d'appréhender de manière globale l'exposition de la filiale sur les différents processus cibles.",
                        ],
                        [
                            'label' => 'Principe 3',
                            'statement' => "Principe de corrélation de l'évaluation des risques avec les objectifs stratégiques et opérationnels",
                            'explanation' => "Il est demandé à chaque évaluateur de procéder à cet exercice en corrélation avec les objectifs opérationnels de ses processus ou sous-processus.",
                        ],
                        [
                            'label' => 'Principe 4',
                            'statement' => "Probabilité du risque est différente de la fréquence de survenance de l'incident",
                            'explanation' => "Un risque jamais survenu peut présenter une probabilité de survenance élevée et vice-versa. L'évaluateur devra donc avoir à l'esprit les facteurs rendant possibles la survenance du risque.",
                        ],
                        [
                            'label' => 'Principe 5',
                            'statement' => 'Évaluation de la gravité',
                            'explanation' => "L'appréciation de la gravité est une estimation très fine des impacts supposés de la survenance du risque sur la réalisation des objectifs de l'organisation. Il conviendra de ne pas surestimer des risques dont les impacts ne seraient que « locaux ». De même, il faudra veiller à ne pas sous-estimer des risques dont l'impact est plus général. L'exercice d'évaluation requiert un élargissement de la perspective au-delà de votre simple domaine (direction, cellule, process, activité).",
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
<p><strong>Étape 2 :</strong> Évaluation du dispositif de contrôle. La maturité du contrôle est calculée <strong>automatiquement</strong>.</p>
<p><strong>Étape 3 :</strong> Détermination du risque résiduel.</p>

<h2 class="mp-section-title">I) Travaux à réaliser par les personnes ressources</h2>

<h3 class="mp-step-title">Étape 1 — Analyse de l'exposition naturelle (ou risque inhérent) :</h3>
<p>Il s'agit de déterminer la probabilité de survenance et la gravité du risque en fonction de l'environnement interne et externe de l'organisation. L'évaluation se fait sur une échelle à 6 niveaux selon deux axes, en se référant à la feuille <a href="/referentials" class="mp-blue">Echelle P &amp; G</a>.</p>

<h3 class="mp-step-title">Étape 2 — Évaluation des dispositifs de contrôles :</h3>
<p>Il s'agit d'attribuer un niveau de maturité au dispositif de contrôle sur une échelle de 1 à 5, en se référant à la feuille <a href="/referentials" class="mp-blue">Echelle de contrôle</a>. L'évaluateur s'appuiera sur une appréciation <strong>conceptuelle</strong> du contrôle ; les phases ultérieures porteront sur l'efficacité <strong>opérationnelle</strong>.</p>

<h3 class="mp-step-title">Étape 3 — Analyse de la vulnérabilité réelle ou risque résiduel :</h3>
<p>Le risque résiduel reflète le niveau de criticité subsistant après prise en compte du dispositif de contrôle interne. Les données alimenteront la cartographie des risques sur la matrice de criticité, en se référant à la feuille <a href="/referentials" class="mp-blue">Matrice</a> (G = X et P = Y).</p>

<p class="mp-footer">L'équipe projet vous remercie de votre collaboration !!!</p>
HTML;
    }
}
