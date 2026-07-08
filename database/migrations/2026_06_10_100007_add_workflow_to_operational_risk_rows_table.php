<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_risk_rows', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->after('entity_id');
            $table->text('revision_comment')->nullable()->after('status');
            $table->foreignId('assigned_entity_id')->nullable()->after('revision_comment')->constrained('entities')->nullOnDelete();
            $table->date('deadline')->nullable()->after('assigned_entity_id');
            $table->foreignId('created_by_id')->nullable()->after('deadline')->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by_id')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('validated_by_id');
            $table->timestamp('validated_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('operational_risk_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_entity_id');
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropConstrainedForeignId('validated_by_id');
            $table->dropColumn([
                'status',
                'revision_comment',
                'deadline',
                'submitted_at',
                'validated_at',
            ]);
        });
    }
};
