<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number')->default(1);
            $table->text('action_plan');
            $table->string('responsible_name')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', [
                'non_demarre',
                'en_cours',
                'en_attente',
                'en_retard',
                'cloture',
                'annule',
            ])->default('non_demarre');
            $table->boolean('is_waiting')->default(false);
            $table->date('transmission_date')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_action_plans');
    }
};
