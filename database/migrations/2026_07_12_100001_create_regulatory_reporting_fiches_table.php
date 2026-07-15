<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->id();
            $table->string('fiche_number', 80)->nullable()->index();
            $table->text('type_reporting')->nullable();
            $table->json('destinataires')->nullable();
            $table->text('reference')->nullable();
            $table->boolean('pj_required')->default(false);
            $table->json('elements')->nullable();
            $table->json('canals')->nullable();
            $table->json('periodicites')->nullable();
            $table->string('deposant')->nullable();
            $table->string('etabli_par')->nullable();
            $table->date('date_validation')->nullable();
            $table->date('delai_transmission')->nullable();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_reporting_fiches');
    }
};
