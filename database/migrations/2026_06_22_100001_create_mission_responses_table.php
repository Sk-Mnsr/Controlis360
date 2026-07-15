<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->cascadeOnDelete();
            $table->enum('response_type', ['action', 'passivite']);
            $table->enum('handling_mode', ['self', 'agent'])->nullable();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('workflow_status', [
                'en_saisie',
                'a_valider',
                'transmis',
            ])->default('en_saisie');
            $table->string('responsible_name')->nullable();
            $table->date('action_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->unsignedTinyInteger('progress_rate')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('needs_infrastructure_change')->default(false);
            $table->decimal('investment_amount', 15, 2)->nullable();
            $table->enum('go_no_go', ['go', 'no_go'])->nullable();
            $table->json('attachment_paths')->nullable();
            $table->text('passivity_comment')->nullable();
            $table->json('passivity_attachment_paths')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamps();

            $table->unique(['mission_id', 'responsable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_responses');
    }
};
