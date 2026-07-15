<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('status', 30)->default('ouvert')->change();
        });

        DB::table('missions')->whereIn('status', ['cloturee', 'traitee'])->update(['status' => 'ferme']);
        DB::table('missions')->whereNotIn('status', ['ferme'])->update(['status' => 'ouvert']);

        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('status', 30)->default('emise')->change();
        });

        DB::table('recommendations')->where('status', 'partiellement_traitee')->update(['status' => 'en_cours']);
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('status', 30)->default('emise')->change();
        });

        DB::table('missions')->where('status', 'ferme')->update(['status' => 'cloturee']);
        DB::table('missions')->where('status', 'ouvert')->update(['status' => 'emise']);

        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('status', 30)->default('emise')->change();
        });
    }
};
