<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->date('issue_date')->nullable()->after('auditor');
        });

        DB::table('missions')
            ->whereNull('issue_date')
            ->update(['issue_date' => DB::raw('start_date')]);
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('issue_date');
        });
    }
};
