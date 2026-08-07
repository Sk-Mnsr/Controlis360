<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gouvernance_it_retroplanning_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')
                ->constrained('gouvernance_it_activities')
                ->cascadeOnDelete();
            $table->string('category', 120)->nullable();
            $table->string('activity', 500)->nullable();
            $table->boolean('is_subheader')->default(false);
            $table->string('due_date', 60)->nullable();
            $table->string('status', 30)->nullable();
            $table->string('owner', 255)->nullable();
            $table->text('comments1')->nullable();
            $table->text('comments2')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['activity_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gouvernance_it_retroplanning_items');
    }
};
