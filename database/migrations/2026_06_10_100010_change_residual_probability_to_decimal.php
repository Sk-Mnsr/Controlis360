<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_risk_rows', function (Blueprint $table) {
            $table->decimal('residual_probability', 4, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('operational_risk_rows', function (Blueprint $table) {
            $table->unsignedTinyInteger('residual_probability')->nullable()->change();
        });
    }
};
