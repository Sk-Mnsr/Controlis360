<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gouvernance_it_retroplanning_ensembles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')
                ->constrained('gouvernance_it_activities')
                ->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['activity_id', 'sort_order']);
        });

        Schema::table('gouvernance_it_retroplanning_items', function (Blueprint $table) {
            $table->foreignId('ensemble_id')
                ->nullable()
                ->after('activity_id')
                ->constrained('gouvernance_it_retroplanning_ensembles')
                ->cascadeOnDelete();
        });

        $activityIds = DB::table('gouvernance_it_retroplanning_items')
            ->distinct()
            ->pluck('activity_id');

        foreach ($activityIds as $activityId) {
            $ensembleId = DB::table('gouvernance_it_retroplanning_ensembles')->insertGetId([
                'activity_id' => $activityId,
                'label' => 'Ensemble 1',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('gouvernance_it_retroplanning_items')
                ->where('activity_id', $activityId)
                ->whereNull('ensemble_id')
                ->update(['ensemble_id' => $ensembleId]);
        }
    }

    public function down(): void
    {
        Schema::table('gouvernance_it_retroplanning_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ensemble_id');
        });

        Schema::dropIfExists('gouvernance_it_retroplanning_ensembles');
    }
};
