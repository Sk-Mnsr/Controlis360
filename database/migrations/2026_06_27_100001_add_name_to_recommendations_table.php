<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('recommendations', 'name')) {
            Schema::table('recommendations', function (Blueprint $table) {
                $table->string('name')->nullable()->after('reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recommendations', 'name')) {
            Schema::table('recommendations', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
