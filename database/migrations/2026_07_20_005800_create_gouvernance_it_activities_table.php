<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gouvernance_it_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->string('module_slug', 40);
            $table->string('section', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title')->nullable();
            $table->string('owner')->nullable();
            $table->enum('statut', ['OPEN', 'CLOSE'])->default('OPEN');
            $table->date('date_livraison')->nullable();
            $table->date('start_date')->nullable();
            $table->date('finish_date')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->text('commentaire')->nullable();
            $table->enum('workflow_status', ['draft', 'saved', 'sent'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['module_slug', 'section', 'environment_id']);
            $table->index(['workflow_status', 'environment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gouvernance_it_activities');
    }
};
