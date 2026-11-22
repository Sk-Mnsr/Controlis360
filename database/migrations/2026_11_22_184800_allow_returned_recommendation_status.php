<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La colonne status est passée en chaîne, mais PostgreSQL a conservé
 * l'ancienne contrainte enum (sans retournee ni transmis).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('recommendations')) {
            return;
        }

        $this->applyConstraint([
            'emise',
            'en_cours',
            'traitee',
            'transmis',
            'retournee',
            'cloturee',
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('recommendations')) {
            return;
        }

        DB::table('recommendations')
            ->whereIn('status', ['transmis', 'retournee'])
            ->update(['status' => 'en_cours']);

        $this->applyConstraint([
            'emise',
            'en_cours',
            'partiellement_traitee',
            'traitee',
            'cloturee',
        ]);
    }

    /**
     * @param  list<string>  $values
     */
    private function applyConstraint(array $values): void
    {
        $arrayList = implode(', ', array_map(
            fn (string $value) => "'{$value}'::character varying",
            $values,
        ));

        DB::statement('ALTER TABLE recommendations DROP CONSTRAINT IF EXISTS recommendations_status_check');
        DB::statement("
            ALTER TABLE recommendations
            ADD CONSTRAINT recommendations_status_check
            CHECK ((status)::text = ANY (ARRAY[{$arrayList}]::text[]))
        ");
    }
};
