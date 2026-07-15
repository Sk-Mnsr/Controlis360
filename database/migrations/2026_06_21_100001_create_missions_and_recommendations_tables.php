<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('mission_recipient');
        Schema::dropIfExists('entity_mission');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('missions');

        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->enum('mission_type', [
                'audit_interne',
                'audit_externe',
                'controle_permanent',
                'inspection',
                'cac',
                'regulateur',
            ]);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('auditor');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('report_reference')->nullable();
            $table->enum('status', [
                'emise',
                'en_cours',
                'partiellement_traitee',
                'traitee',
                'cloturee',
            ])->default('emise');
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('entity_mission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['mission_id', 'entity_id']);
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->date('recommendation_date');
            $table->enum('risk_level', ['faible', 'moyen', 'eleve', 'critique']);
            $table->enum('priority', ['basse', 'moyenne', 'haute']);
            $table->string('responsible_name')->nullable();
            $table->date('due_date')->nullable();
            $table->string('recommendation_label');
            $table->text('recommendation_details')->nullable();
            $table->timestamps();
        });

        Schema::create('mission_recipient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['mission_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_recipient');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('entity_mission');
        Schema::dropIfExists('missions');
    }
};
