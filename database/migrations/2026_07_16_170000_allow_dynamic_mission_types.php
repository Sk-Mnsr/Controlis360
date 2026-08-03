<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('missions') || ! Schema::hasColumn('missions', 'mission_type')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE missions DROP CONSTRAINT IF EXISTS missions_mission_type_check');
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE missions MODIFY mission_type VARCHAR(100) NOT NULL');
        }
    }

    public function down(): void
    {
        // La liste des types est désormais pilotée par la table mission_types.
    }
};
