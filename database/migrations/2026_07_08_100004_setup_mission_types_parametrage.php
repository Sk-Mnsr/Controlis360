<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mission_types')) {
            Schema::create('mission_types', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->json('profiles')->nullable();
                $table->text('description')->nullable();
                $table->string('accent_color', 20)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('mission_types', function (Blueprint $table) {
                if (! Schema::hasColumn('mission_types', 'profiles')) {
                    $table->json('profiles')->nullable()->after('name');
                }
                if (! Schema::hasColumn('mission_types', 'description')) {
                    $table->text('description')->nullable()->after('profiles');
                }
                if (! Schema::hasColumn('mission_types', 'accent_color')) {
                    $table->string('accent_color', 20)->nullable()->after('description');
                }
            });
        }

        $legacyCodeMap = [
            'AUDIT_INTERNE' => 'audit_interne',
            'AUDIT_EXTERNE' => 'audit_externe',
            'CONTROLE_PERMANENT' => 'controle_permanent',
            'INSPECTION' => 'inspection',
            'CAC' => 'cac',
            'REGULATEUR' => 'regulateur',
        ];

        foreach ($legacyCodeMap as $legacyCode => $normalizedCode) {
            DB::table('mission_types')
                ->where('code', $legacyCode)
                ->update(['code' => $normalizedCode]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mission_types')) {
            Schema::table('mission_types', function (Blueprint $table) {
                if (Schema::hasColumn('mission_types', 'accent_color')) {
                    $table->dropColumn('accent_color');
                }
                if (Schema::hasColumn('mission_types', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('mission_types', 'profiles')) {
                    $table->dropColumn('profiles');
                }
            });
        }
    }
};
