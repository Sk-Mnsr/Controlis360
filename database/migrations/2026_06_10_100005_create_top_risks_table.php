<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_risks', function (Blueprint $table) {
            $table->id();
            $table->string('process_name')->nullable();
            $table->string('sub_process_name');
            $table->text('major_exceptions')->nullable();
            $table->string('risk_family')->nullable();
            $table->unsignedTinyInteger('gravity')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_risks');
    }
};
