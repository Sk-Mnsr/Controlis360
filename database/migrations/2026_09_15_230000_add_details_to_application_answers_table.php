<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('application_answers', 'details')) {
                $table->text('details')->nullable()->after('value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_answers', function (Blueprint $table) {
            if (Schema::hasColumn('application_answers', 'details')) {
                $table->dropColumn('details');
            }
        });
    }
};
