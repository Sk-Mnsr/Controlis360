<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gouvernance_it_ensembles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->string('module_slug', 40);
            $table->string('label');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['module_slug', 'environment_id']);
        });

        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->foreignId('ensemble_id')
                ->nullable()
                ->after('entity_id')
                ->constrained('gouvernance_it_ensembles')
                ->cascadeOnDelete();
        });

        // Rattacher les lignes existantes à un ensemble par défaut.
        $groups = DB::table('gouvernance_it_activities')
            ->select('module_slug', 'environment_id', 'entity_id')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $ensembleId = DB::table('gouvernance_it_ensembles')->insertGetId([
                'environment_id' => $group->environment_id,
                'entity_id' => $group->entity_id,
                'module_slug' => $group->module_slug,
                'label' => 'Ensemble du '.now()->format('d/m/Y H:i'),
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('gouvernance_it_activities')
                ->where('module_slug', $group->module_slug)
                ->where(function ($query) use ($group) {
                    if ($group->environment_id === null) {
                        $query->whereNull('environment_id');
                    } else {
                        $query->where('environment_id', $group->environment_id);
                    }
                })
                ->update(['ensemble_id' => $ensembleId]);
        }
    }

    public function down(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ensemble_id');
        });

        Schema::dropIfExists('gouvernance_it_ensembles');
    }
};
