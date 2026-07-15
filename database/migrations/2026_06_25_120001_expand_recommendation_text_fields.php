<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->text('theme')->nullable()->change();
            $table->text('risk_type')->nullable()->change();
            $table->text('recommendation_label')->change();
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('theme')->nullable()->change();
            $table->string('risk_type')->nullable()->change();
            $table->string('recommendation_label')->change();
        });
    }
};
