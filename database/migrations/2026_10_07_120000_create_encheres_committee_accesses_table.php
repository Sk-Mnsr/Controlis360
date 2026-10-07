<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encheres_committee_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('auction_key')->unique();
            $table->string('lot')->nullable();
            $table->string('title');
            $table->string('code_hash');
            $table->json('committee');
            $table->json('bids')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encheres_committee_accesses');
    }
};
