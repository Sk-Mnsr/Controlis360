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
        'active' => false,
        'coming_soon' => true,
        'entry_route' => null,
    ],
    'conformite' => [
        'slug' => 'conformite',
        'name' => 'Conformité',
        'description' => 'Suivi réglementaire et dispositifs de conformité.',
        'active' => false,
        'coming_soon' => true,
        'entry_route' => null,
    ],
];
