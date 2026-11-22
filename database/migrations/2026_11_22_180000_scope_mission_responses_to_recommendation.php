<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_responses', function (Blueprint $table) {
            $table->foreignId('recommendation_id')
                ->nullable()
                ->after('mission_id')
                ->constrained('recommendations')
                ->nullOnDelete();
            $table->dropUnique(['mission_id', 'responsable_id']);
        });

        DB::statement('CREATE UNIQUE INDEX mission_responses_reco_owner_unique ON mission_responses (mission_id, responsable_id, recommendation_id) WHERE recommendation_id IS NOT NULL');
        DB::statement("CREATE UNIQUE INDEX mission_responses_passivity_unique ON mission_responses (mission_id, responsable_id) WHERE response_type = 'passivite'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS mission_responses_reco_owner_unique');
        DB::statement('DROP INDEX IF EXISTS mission_responses_passivity_unique');

        Schema::table('mission_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recommendation_id');
            $table->unique(['mission_id', 'responsable_id']);
        });
    }
};
