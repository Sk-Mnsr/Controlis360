<?php

return [
    'cartographie' => [
        'slug' => 'cartographie',
        'name' => 'Cartographie des risques',
        'description' => 'Identification, évaluation et pilotage des risques opérationnels.',
        'active' => true,
        'entry_route' => 'cartographie.home',
    ],
    'audit' => [
        'slug' => 'audit',
        'name' => 'Suivi des reco',
        'description' => 'Planification et suivi des recommandations.',
        'active' => true,
        'coming_soon' => false,
        'entry_route' => 'audit.home',
    ],
    'conformite' => [
        'slug' => 'conformite',
        'name' => 'Conformité',
        'description' => 'Suivi réglementaire et dispositifs de conformité.',
        'active' => true,
        'coming_soon' => false,
        'entry_route' => 'conformite.home',
    ],
    'gouvernance_it' => [
        'slug' => 'gouvernance-it',
        'name' => 'Gouvernance IT',
        'description' => 'Pilotage des risques, contrôles et conformité des systèmes d’information.',
        'active' => true,
        'coming_soon' => false,
        'entry_route' => 'gouvernance-it.home',
    ],
];
