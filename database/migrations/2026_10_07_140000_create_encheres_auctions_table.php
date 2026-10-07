<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encheres_auctions', function (Blueprint $table) {
            $table->id();
            $table->string('auction_key')->unique();
            $table->boolean('published')->default(false);
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encheres_auctions');
    }
};
