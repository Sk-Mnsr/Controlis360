<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->text('impact')->nullable()->after('lead_time_days');
        });
    }

    public function down(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->dropColumn('impact');
        });
    }
};
