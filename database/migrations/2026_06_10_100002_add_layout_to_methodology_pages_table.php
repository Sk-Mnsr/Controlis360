<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methodology_pages', function (Blueprint $table) {
            $table->string('layout')->default('classic')->after('slug');
            $table->text('body_html')->nullable()->after('conclusion');
        });
    }

    public function down(): void
    {
        Schema::table('methodology_pages', function (Blueprint $table) {
            $table->dropColumn(['layout', 'body_html']);
        });
    }
};
