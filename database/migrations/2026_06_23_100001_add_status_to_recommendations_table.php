<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('recommendations', 'status')) {
            Schema::table('recommendations', function (Blueprint $table) {
                $table->enum('status', [
                    'emise',
                    'en_cours',
                    'partiellement_traitee',
                    'traitee',
                    'cloturee',
                ])->default('emise')->after('priority');
            });
        }

        $rows = DB::table('recommendations')
            ->join('missions', 'missions.id', '=', 'recommendations.mission_id')
            ->where('recommendations.status', 'emise')
            ->select('recommendations.id as reco_id', 'missions.status as mission_status')
            ->get();

        foreach ($rows as $row) {
            if ($row->mission_status !== 'emise') {
                DB::table('recommendations')->where('id', $row->reco_id)->update(['status' => $row->mission_status]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
