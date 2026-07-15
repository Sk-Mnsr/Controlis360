<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'environment_id']);
        });

        Schema::create('entity_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'entity_id']);
        });

        if (Schema::hasColumn('users', 'environment_id')) {
            $users = DB::table('users')->get(['id', 'environment_id', 'entity_id']);

            foreach ($users as $user) {
                if ($user->environment_id) {
                    DB::table('environment_user')->insertOrIgnore([
                        'user_id' => $user->id,
                        'environment_id' => $user->environment_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($user->entity_id) {
                    DB::table('entity_user')->insertOrIgnore([
                        'user_id' => $user->id,
                        'entity_id' => $user->entity_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('environment_id');
                $table->dropConstrainedForeignId('entity_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
        });

        $environmentLinks = DB::table('environment_user')
            ->select('user_id', DB::raw('MIN(environment_id) as environment_id'))
            ->groupBy('user_id')
            ->get();

        foreach ($environmentLinks as $link) {
            DB::table('users')->where('id', $link->user_id)->update([
                'environment_id' => $link->environment_id,
            ]);
        }

        $entityLinks = DB::table('entity_user')
            ->select('user_id', DB::raw('MIN(entity_id) as entity_id'))
            ->groupBy('user_id')
            ->get();

        foreach ($entityLinks as $link) {
            DB::table('users')->where('id', $link->user_id)->update([
                'entity_id' => $link->entity_id,
            ]);
        }

        Schema::dropIfExists('entity_user');
        Schema::dropIfExists('environment_user');
    }
};
