<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_action_plan_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_action_plan_id')
                ->constrained('recommendation_action_plans')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('author_name')->nullable();
            $table->date('commented_at');
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_action_plan_comments');
    }
};
