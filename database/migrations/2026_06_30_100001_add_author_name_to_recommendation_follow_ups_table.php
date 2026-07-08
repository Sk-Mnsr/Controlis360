<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_follow_ups', function (Blueprint $table) {
            $table->string('author_name')->nullable()->after('user_id');
        });

        DB::table('recommendation_follow_ups')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $name = DB::table('users')->where('id', $row->user_id)->value('name');

                    if ($name) {
                        DB::table('recommendation_follow_ups')
                            ->where('id', $row->id)
                            ->update(['author_name' => $name]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('recommendation_follow_ups', function (Blueprint $table) {
            $table->dropColumn('author_name');
        });
    }
};
