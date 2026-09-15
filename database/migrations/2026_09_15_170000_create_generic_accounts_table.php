<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generic_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->string('user_id', 120);
            $table->string('user_name')->nullable();
            $table->string('statut', 80)->nullable();
            $table->string('forgotten', 20)->nullable();
            $table->string('account_type')->nullable();
            $table->text('purpose')->nullable();
            $table->string('system_application')->nullable();
            $table->string('owner')->nullable();
            $table->string('usage')->nullable();
            $table->string('privileges_role')->nullable();
            $table->string('associated_nominative_account')->nullable();
            $table->date('last_review_date')->nullable();
            $table->text('action_observation')->nullable();
            $table->text('existence_justification')->nullable();
            $table->string('usage_mode', 40)->nullable();
            $table->string('interactive_access', 20)->nullable();
            $table->string('password_managed_by')->nullable();
            $table->string('mfa', 40)->nullable();
            $table->string('logging_enabled', 20)->nullable();
            $table->string('periodic_review', 20)->nullable();
            $table->string('risk', 40)->nullable();
            $table->text('corrective_measure')->nullable();
            $table->string('workflow_status', 40)->default('pending_validation');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['environment_id', 'workflow_status']);
            $table->index(['user_id', 'environment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generic_accounts');
    }
};
