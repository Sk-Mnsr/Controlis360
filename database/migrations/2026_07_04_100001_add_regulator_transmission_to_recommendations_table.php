<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->timestamp('regulator_transmitted_at')->nullable()->after('attachment_paths');
            $table->foreignId('regulator_transmitted_by')->nullable()->after('regulator_transmitted_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regulator_transmitted_by');
            $table->dropColumn('regulator_transmitted_at');
        });
    }
};
