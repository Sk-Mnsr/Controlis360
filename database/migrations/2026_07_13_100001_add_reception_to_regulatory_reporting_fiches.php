<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->foreignId('etabli_par_entity_id')
                ->nullable()
                ->after('etabli_par')
                ->constrained('entities')
                ->nullOnDelete();
            $table->string('status', 30)->default('envoyee')->after('created_by')->index();
        });

        Schema::create('regulatory_reporting_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_id')->constrained('regulatory_reporting_fiches')->cascadeOnDelete();
            $table->string('valeur')->nullable();
            $table->text('contenu')->nullable();
            $table->string('nom')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_reporting_contributions');

        Schema::table('regulatory_reporting_fiches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etabli_par_entity_id');
            $table->dropColumn('status');
        });
    }
};
