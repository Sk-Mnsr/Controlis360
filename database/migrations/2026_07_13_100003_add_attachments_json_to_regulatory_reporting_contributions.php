<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regulatory_reporting_contributions', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('contenu');
        });

        $rows = DB::table('regulatory_reporting_contributions')->get(['id', 'nom', 'attachment_path']);
        foreach ($rows as $row) {
            $attachments = [];
            if ($row->attachment_path || $row->nom) {
                $attachments[] = [
                    'nom' => $row->nom,
                    'path' => $row->attachment_path,
                ];
            }
            DB::table('regulatory_reporting_contributions')
                ->where('id', $row->id)
                ->update(['attachments' => json_encode($attachments, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        Schema::table('regulatory_reporting_contributions', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
