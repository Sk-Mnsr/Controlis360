<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pci_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('pci_documents', 'attachment_paths')) {
                $table->json('attachment_paths')->nullable()->after('observations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pci_documents', function (Blueprint $table) {
            if (Schema::hasColumn('pci_documents', 'attachment_paths')) {
                $table->dropColumn('attachment_paths');
            }
        });
    }
};
