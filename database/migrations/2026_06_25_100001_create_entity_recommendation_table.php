<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_recommendation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recommendation_id', 'entity_id']);
        });

        if (Schema::hasColumn('recommendations', 'entity_id')) {
            DB::table('recommendations')
                ->whereNotNull('entity_id')
                ->orderBy('id')
                ->each(function ($recommendation) {
                    DB::table('entity_recommendation')->insertOrIgnore([
                        'recommendation_id' => $recommendation->id,
                        'entity_id' => $recommendation->entity_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            Schema::table('recommendations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('entity_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('mission_id')->constrained()->nullOnDelete();
        });

        $links = DB::table('entity_recommendation')
            ->select('recommendation_id', DB::raw('MIN(entity_id) as entity_id'))
            ->groupBy('recommendation_id')
            ->get();

        foreach ($links as $link) {
            DB::table('recommendations')
                ->where('id', $link->recommendation_id)
                ->update(['entity_id' => $link->entity_id]);
        }

        Schema::dropIfExists('entity_recommendation');
    }
};
