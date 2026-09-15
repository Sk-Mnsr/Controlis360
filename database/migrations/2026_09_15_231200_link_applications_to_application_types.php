<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'application_type_id')) {
                $table->foreignId('application_type_id')
                    ->nullable()
                    ->after('entity_id')
                    ->constrained('application_types')
                    ->nullOnDelete();
            }

            foreach ([
                'licenses_count' => 'string',
                'license_type' => 'string',
                'customization_level' => 'string',
                'etp_support' => 'text',
                'etp_changes' => 'text',
                'archi_ho' => 'string',
            ] as $column => $type) {
                if (Schema::hasColumn('applications', $column)) {
                    continue;
                }

                if ($type === 'text') {
                    $table->text($column)->nullable();
                } else {
                    $table->string($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'application_type_id')) {
                $table->dropConstrainedForeignId('application_type_id');
            }

            foreach (['licenses_count', 'license_type', 'customization_level', 'etp_support', 'etp_changes', 'archi_ho'] as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
