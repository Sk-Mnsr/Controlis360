<?php

return [
    'mission_types' => [
        ['value' => 'audit_interne', 'label' => 'Audit Interne', 'profiles' => ['audit']],
        ['value' => 'audit_externe', 'label' => 'Audit Externe', 'profiles' => ['audit']],
        ['value' => 'controle_permanent', 'label' => 'Contrôle Permanent', 'profiles' => ['controle']],
        ['value' => 'inspection', 'label' => 'Inspection', 'profiles' => ['controle']],
        ['value' => 'cac', 'label' => 'CAC', 'profiles' => ['audit']],
        ['value' => 'regulateur', 'label' => 'Régulateur', 'profiles' => ['audit', 'controle']],
    ],
    'risk_levels' => [
        ['value' => 'faible', 'label' => 'Faible'],
        ['value' => 'moyen', 'label' => 'Moyen'],
        ['value' => 'eleve', 'label' => 'Élevé'],
        ['value' => 'critique', 'label' => 'Critique'],
    ],
    'priorities' => [
        ['value' => 'basse', 'label' => 'Basse'],
        ['value' => 'moyenne', 'label' => 'Moyenne'],
        ['value' => 'haute', 'label' => 'Haute'],
    ],
    'statuses' => [
        ['value' => 'ouvert', 'label' => 'Ouvert', 'color' => 'blue'],
        ['value' => 'ferme', 'label' => 'Fermé', 'color' => 'slate'],
    ],
    'recommendation_statuses' => [
        ['value' => 'emise', 'label' => 'Émise', 'color' => 'slate'],
        ['value' => 'en_cours', 'label' => 'En cours', 'color' => 'amber'],
        ['value' => 'traitee', 'label' => 'Traitée', 'color' => 'emerald'],
        ['value' => 'transmis', 'label' => 'Transmis', 'color' => 'blue'],
        ['value' => 'cloturee', 'label' => 'Clôturée', 'color' => 'slate'],
    ],
    'action_plan_statuses' => [
        ['value' => 'non_demarre', 'label' => 'Non démarré', 'color' => 'blue'],
        ['value' => 'en_cours', 'label' => 'En cours', 'color' => 'amber'],
        ['value' => 'en_attente', 'label' => 'En attente', 'color' => 'orange'],
        ['value' => 'en_retard', 'label' => 'En retard', 'color' => 'red'],
        ['value' => 'cloture', 'label' => 'Clôturé', 'color' => 'emerald'],
        ['value' => 'annule', 'label' => 'Annulé', 'color' => 'slate'],
    ],
];
