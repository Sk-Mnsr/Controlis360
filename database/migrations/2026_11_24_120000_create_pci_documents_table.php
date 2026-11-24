<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pci_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->string('reference', 40);
            $table->string('document_name');
            $table->string('category', 120)->nullable();
            $table->string('direction', 160)->nullable();
            $table->string('gouvernance_owner', 160)->nullable();
            $table->string('controle_interne', 160)->nullable();
            $table->string('responsible', 160)->nullable();
            $table->date('created_on')->nullable();
            $table->date('last_review_date')->nullable();
            $table->unsignedSmallInteger('periodicity_months')->default(12);
            $table->date('validated_on')->nullable();
            $table->string('validated_by', 160)->nullable();
            $table->string('version', 40)->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['environment_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pci_documents');
    }
};
