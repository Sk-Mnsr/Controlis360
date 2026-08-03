<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('missions') || ! Schema::hasColumn('missions', 'status')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE missions DROP CONSTRAINT IF EXISTS missions_status_check');
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE missions MODIFY status VARCHAR(30) NOT NULL DEFAULT 'ouvert'");
        }
    }

    public function down(): void
    {
        // Les statuts sont désormais pilotés par l'application (ouvert / ferme).
    }
};
