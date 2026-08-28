<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->unique();
            $table->string('name');
            $table->string('business_domain')->nullable();
            $table->text('main_function')->nullable();
            $table->string('users')->nullable();
            $table->string('technology')->nullable();
            $table->string('type')->nullable();
            $table->string('criticality', 30)->nullable();
            $table->string('status', 30)->default('active');
            $table->string('owner')->nullable();
            $table->date('go_live_date')->nullable();

            // Licences
            $table->string('license_version')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('license_update')->nullable();

            // Renouvellement licence
            $table->date('license_last_renewal_date')->nullable();
            $table->date('license_next_renewal_date')->nullable();

            // Infrastructure & risque
            $table->string('infrastructure')->nullable();
            $table->string('hosting_type')->nullable();
            $table->string('backup')->nullable();
            $table->string('sla')->nullable();
            $table->text('comment')->nullable();
            $table->string('cost')->nullable();
            $table->string('impact')->nullable();
            $table->string('risk')->nullable();
            $table->string('last_version')->nullable();

            $table->foreignId('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'criticality']);
            $table->index('environment_id');
            $table->index('business_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
