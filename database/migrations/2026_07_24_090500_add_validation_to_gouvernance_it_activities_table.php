<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->string('validation_status', 30)->nullable()->after('workflow_status');
            $table->foreignId('validated_by')->nullable()->after('validation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->timestamp('submitted_for_validation_at')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('gouvernance_it_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['validation_status', 'validated_at', 'submitted_for_validation_at']);
        });
    }
};
