<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('number');
        });

        Schema::create('risk_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('definition')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('scale_levels', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['gravity', 'probability', 'control']);
            $table->unsignedTinyInteger('level');
            $table->string('label');
            $table->string('qualification')->nullable();
            $table->text('description')->nullable();
            $table->string('maturity_label')->nullable();
            $table->timestamps();

            $table->unique(['type', 'level']);
        });

        Schema::create('risk_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->unsignedTinyInteger('min_score');
            $table->unsignedTinyInteger('max_score');
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_classifications');
        Schema::dropIfExists('scale_levels');
        Schema::dropIfExists('risk_families');
        Schema::dropIfExists('risk_categories');
    }
};
