<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('mission_id');
            $table->text('comments')->nullable()->after('recommendation_details');
            $table->json('attachment_paths')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropColumn(['reference', 'comments', 'attachment_paths']);
        });
    }
};
