<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pci_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('pci_documents', 'workflow_status')) {
                $table->string('workflow_status', 40)->default('validated')->after('observations');
            }
            if (! Schema::hasColumn('pci_documents', 'workflow_validated_by')) {
                $table->foreignId('workflow_validated_by')->nullable()->after('workflow_status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('pci_documents', 'workflow_validated_at')) {
                $table->timestamp('workflow_validated_at')->nullable()->after('workflow_validated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pci_documents', function (Blueprint $table) {
            if (Schema::hasColumn('pci_documents', 'workflow_validated_by')) {
                $table->dropConstrainedForeignId('workflow_validated_by');
            }
            if (Schema::hasColumn('pci_documents', 'workflow_validated_at')) {
                $table->dropColumn('workflow_validated_at');
            }
            if (Schema::hasColumn('pci_documents', 'workflow_status')) {
                $table->dropColumn('workflow_status');
            }
        });
    }
};
