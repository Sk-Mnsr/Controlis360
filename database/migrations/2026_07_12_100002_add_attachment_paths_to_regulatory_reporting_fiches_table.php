<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->json('attachment_paths')->nullable()->after('pj_required');
        });
    }

    public function down(): void
    {
        Schema::table('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->dropColumn('attachment_paths');
        });
    }
};
