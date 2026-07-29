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

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_metier_role_check');
        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_metier_role_check
            CHECK (
                metier_role IS NULL
                OR (metier_role)::text = ANY (ARRAY[
                    'responsable_entite'::character varying,
                    'groupe'::character varying,
                    'visiteur'::character varying,
                    'agent'::character varying
                ]::text[])
            )
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_metier_role_check');
        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_metier_role_check
            CHECK (
                metier_role IS NULL
                OR (metier_role)::text = ANY (ARRAY[
                    'responsable_entite'::character varying,
                    'groupe'::character varying,
                    'visiteur'::character varying
                ]::text[])
            )
        ");
    }
};
