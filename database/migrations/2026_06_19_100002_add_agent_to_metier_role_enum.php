<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateMetierRoleConstraint([
            'responsable_entite',
            'groupe',
            'visiteur',
            'agent',
        ]);
    }

    public function down(): void
    {
        $this->updateMetierRoleConstraint([
            'responsable_entite',
            'groupe',
            'visiteur',
        ]);
    }

    /**
     * @param  list<string>  $values
     */
    private function updateMetierRoleConstraint(array $values): void
    {
        $driver = DB::getDriverName();
        $enumList = implode("', '", $values);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY metier_role ENUM('{$enumList}') NULL");

            return;
        }

        if ($driver === 'pgsql') {
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
    }
};
