<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->enum('priorite', ['P1', 'P2', 'P3'])->nullable()->after('owner');
        });
    }

    public function down(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->dropColumn('priorite');
        });
    }
};
