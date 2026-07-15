<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->foreignId('deposant_entity_id')
                ->nullable()
                ->after('deposant')
                ->constrained('entities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deposant_entity_id');
        });
    }
};
