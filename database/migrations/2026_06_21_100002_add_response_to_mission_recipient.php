<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_recipient', function (Blueprint $table) {
            $table->enum('response', ['action', 'passivite'])->nullable()->after('notified_at');
            $table->timestamp('responded_at')->nullable()->after('response');
        });
    }

    public function down(): void
    {
        Schema::table('mission_recipient', function (Blueprint $table) {
            $table->dropColumn(['response', 'responded_at']);
        });
    }
};
