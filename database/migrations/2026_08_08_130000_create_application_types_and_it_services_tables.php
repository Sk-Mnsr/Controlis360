<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('accent_color', 20)->default('#0f4c81');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('it_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_type_id')->unique()->constrained('application_types')->cascadeOnDelete();
            $table->string('exists_flag', 10)->nullable(); // oui / non
            $table->string('solution_name')->nullable();
            $table->string('editor')->nullable();
            $table->string('importance')->nullable();
            $table->string('version')->nullable();
            $table->string('last_version')->nullable();
            $table->string('sla_exists', 10)->nullable();
            $table->string('hosting_mode')->nullable(); // SaaS / on-site / Cloud
            $table->string('users_count')->nullable();
            $table->string('licenses_count')->nullable();
            $table->string('license_type')->nullable();
            $table->string('customization_level')->nullable();
            $table->string('backups', 10)->nullable();
            $table->text('etp_support')->nullable();
            $table->string('archi_ho', 10)->nullable();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('application_questions', function (Blueprint $table) {
            $table->id();
            // generic | security | type
            $table->string('scope', 30);
            $table->foreignId('application_type_id')->nullable()->constrained('application_types')->cascadeOnDelete();
            $table->string('label');
            $table->text('help')->nullable();
            $table->string('input_type', 30)->default('text'); // text, textarea, yes_no, select
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['scope', 'application_type_id', 'is_active']);
        });

        Schema::create('application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('application_questions')->cascadeOnDelete();
            // null for generic/security scopes
            $table->foreignId('application_type_id')->nullable()->constrained('application_types')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->foreignId('answered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

        });

        // NULL distincts dans UNIQUE PostgreSQL → index partiels
        DB::statement('CREATE UNIQUE INDEX app_answers_question_type_unique ON application_answers (question_id, application_type_id) WHERE application_type_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX app_answers_global_question_unique ON application_answers (question_id) WHERE application_type_id IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS app_answers_global_question_unique');
        DB::statement('DROP INDEX IF EXISTS app_answers_question_type_unique');
        Schema::dropIfExists('application_answers');
        Schema::dropIfExists('application_questions');
        Schema::dropIfExists('it_services');
        Schema::dropIfExists('application_types');
    }
};
