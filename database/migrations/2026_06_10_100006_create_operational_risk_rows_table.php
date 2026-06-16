<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_risk_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('process_number')->nullable();
            $table->string('process_name')->nullable();
            $table->decimal('ratio', 5, 2)->nullable();
            $table->string('sub_process_name');
            $table->text('major_exceptions')->nullable();
            $table->text('correlated_risks')->nullable();
            $table->string('risk_family')->nullable();
            $table->unsignedTinyInteger('gravity')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->text('control_description')->nullable();
            $table->boolean('control_exists')->nullable();
            $table->string('control_owner')->nullable();
            $table->unsignedTinyInteger('control_effectiveness')->nullable();
            $table->unsignedTinyInteger('residual_gravity')->nullable();
            $table->unsignedTinyInteger('residual_probability')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_risk_rows');
    }
};
