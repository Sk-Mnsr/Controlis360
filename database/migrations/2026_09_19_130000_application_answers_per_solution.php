<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les réponses du questionnaire « type » passent du type applicatif (CBS…)
 * à la solution inventaire (application_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('application_answers', 'application_id')) {
                $table->foreignId('application_id')
                    ->nullable()
                    ->after('application_type_id')
                    ->constrained('applications')
                    ->cascadeOnDelete();
            }
        });

        // Répliquer les réponses existantes (par type) sur chaque solution du type.
        $legacy = DB::table('application_answers')
            ->whereNotNull('application_type_id')
            ->whereNull('application_id')
            ->orderBy('id')
            ->get();

        foreach ($legacy as $answer) {
            $appIds = DB::table('applications')
                ->where('application_type_id', $answer->application_type_id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->pluck('id');

            if ($appIds->isEmpty()) {
                DB::table('application_answers')->where('id', $answer->id)->delete();

                continue;
            }

            $first = true;
            foreach ($appIds as $appId) {
                if ($first) {
                    DB::table('application_answers')
                        ->where('id', $answer->id)
                        ->update(['application_id' => $appId]);
                    $first = false;

                    continue;
                }

                DB::table('application_answers')->insert([
                    'question_id' => $answer->question_id,
                    'application_type_id' => $answer->application_type_id,
                    'application_id' => $appId,
                    'value' => $answer->value,
                    'details' => $answer->details ?? null,
                    'answered_by_id' => $answer->answered_by_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::statement('DROP INDEX IF EXISTS app_answers_question_type_unique');
        DB::statement('DROP INDEX IF EXISTS app_answers_global_question_unique');

        DB::statement(
            'CREATE UNIQUE INDEX app_answers_question_application_unique
             ON application_answers (question_id, application_id)
             WHERE application_id IS NOT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX app_answers_global_question_unique
             ON application_answers (question_id)
             WHERE application_id IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS app_answers_question_application_unique');
        DB::statement('DROP INDEX IF EXISTS app_answers_global_question_unique');

        // Revenir à une réponse unique par type : garder la première solution.
        $rows = DB::table('application_answers')
            ->whereNotNull('application_id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($r) => $r->question_id.'-'.$r->application_type_id);

        foreach ($rows as $group) {
            $keep = $group->first();
            $dropIds = $group->skip(1)->pluck('id');
            if ($dropIds->isNotEmpty()) {
                DB::table('application_answers')->whereIn('id', $dropIds)->delete();
            }
            DB::table('application_answers')
                ->where('id', $keep->id)
                ->update(['application_id' => null]);
        }

        DB::statement(
            'CREATE UNIQUE INDEX app_answers_question_type_unique
             ON application_answers (question_id, application_type_id)
             WHERE application_type_id IS NOT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX app_answers_global_question_unique
             ON application_answers (question_id)
             WHERE application_type_id IS NULL'
        );

        Schema::table('application_answers', function (Blueprint $table) {
            if (Schema::hasColumn('application_answers', 'application_id')) {
                $table->dropConstrainedForeignId('application_id');
            }
        });
    }
};
