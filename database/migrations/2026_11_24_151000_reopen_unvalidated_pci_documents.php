<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pci_documents')
            ->whereNull('workflow_validated_by')
            ->update([
                'workflow_status' => 'pending_validation',
                'workflow_validated_at' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('pci_documents')
            ->where('workflow_status', 'pending_validation')
            ->whereNull('workflow_validated_by')
            ->update([
                'workflow_status' => 'validated',
            ]);
    }
};
