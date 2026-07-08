<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = storage_path('app/templates/modele-import-missions.xlsx');
app(App\Services\MissionImportService::class)->buildTemplateFile($path);

echo "Template généré : {$path}\n";
