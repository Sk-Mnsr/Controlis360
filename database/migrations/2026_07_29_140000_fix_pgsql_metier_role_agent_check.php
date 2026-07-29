<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige la contrainte PostgreSQL users_metier_role_check pour inclure "agent".
 * L'ancienne migration 2026_06_19_100002 ne s'exécutait que sur MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        $this->applyConstraint([
            'responsable_entite',
            'groupe',
            'visiteur',
            'agent',
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        // Des lignes peuvent encore avoir metier_role = agent.
        DB::table('users')
            ->where('metier_role', 'agent')
            ->update(['metier_role' => null]);

        $this->applyConstraint([
            'responsable_entite',
            'groupe',
            'visiteur',
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

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_metier_role_check');
        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_metier_role_check
            CHECK (
                metier_role IS NULL
                OR (metier_role)::text = ANY (ARRAY[{$arrayList}]::text[])
            )
        ");
    }
};
