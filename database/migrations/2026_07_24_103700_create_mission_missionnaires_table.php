<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_missionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->string('nom');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->string('poste')->nullable();
            $table->enum('entite_type', ['interne', 'externe'])->default('interne');
            $table->string('responsable_equipe')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_missionnaires');
    }
};
