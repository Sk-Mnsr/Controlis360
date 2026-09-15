<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_services', function (Blueprint $table) {
            if (! Schema::hasColumn('it_services', 'etp_changes')) {
                $table->text('etp_changes')->nullable()->after('etp_support');
            }
        });

        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'editor')) {
                $table->string('editor')->nullable()->after('comment');
            }
            if (! Schema::hasColumn('applications', 'importance')) {
                $table->string('importance')->nullable()->after('editor');
            }
            if (! Schema::hasColumn('applications', 'version')) {
                $table->string('version')->nullable()->after('importance');
            }
        });

        Schema::create('it_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_link')->nullable();
            $table->string('provider')->nullable();
            $table->string('beneficiary')->nullable();
            $table->text('scope')->nullable();
            $table->string('contract_type')->nullable(); // L / S / I / M
            $table->string('volume')->nullable();
            $table->string('cost')->nullable();
            $table->string('cost_mechanism')->nullable();
            $table->string('importance')->nullable();
            $table->string('start_date')->nullable();
            $table->string('duration')->nullable();
            $table->string('sla')->nullable();
            $table->string('termination_notice')->nullable();
            $table->string('signed_both', 20)->nullable();
            $table->string('discount')->nullable();
            $table->string('purchase_owner')->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('it_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('priority')->nullable();
            $table->string('name');
            $table->text('objective')->nullable();
            $table->string('status')->nullable();
            $table->unsignedTinyInteger('progress_pct')->nullable();
            $table->text('results_benefits')->nullable();
            $table->string('owner')->nullable();
            $table->text('comment')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('delivery_date')->nullable();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_projects');
        Schema::dropIfExists('it_contracts');

        Schema::table('applications', function (Blueprint $table) {
            foreach (['editor', 'importance', 'version'] as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('it_services', function (Blueprint $table) {
            if (Schema::hasColumn('it_services', 'etp_changes')) {
                $table->dropColumn('etp_changes');
            }
        });
    }
};
