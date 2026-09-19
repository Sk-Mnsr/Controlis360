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
                    ->constrained('applications')
                    ->cascadeOnDelete();
            }
        });

        // Lever les unicités par type AVANT la duplication (sinon insert impossible).
        DB::statement('DROP INDEX IF EXISTS app_answers_question_type_unique');
        DB::statement('DROP INDEX IF EXISTS app_answers_global_question_unique');
        DB::statement('DROP INDEX IF EXISTS app_answers_question_application_unique');

        // 1) Réponses encore sans application_id → rattacher / dupliquer.
        $legacy = DB::table('application_answers')
            ->whereNotNull('application_type_id')
            ->whereNull('application_id')
            ->orderBy('id')
            ->get();

        foreach ($legacy as $answer) {
            $this->spreadAnswerToTypeApplications($answer);
        }

        // 2) Reprise après échec partiel : compléter les solutions manquantes.
        $typed = DB::table('application_answers')
            ->whereNotNull('application_type_id')
            ->whereNotNull('application_id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => $row->question_id.'|'.$row->application_type_id);

        foreach ($typed as $group) {
            $sample = $group->first();
            $appIds = $this->applicationIdsForType((int) $sample->application_type_id);
            $existing = $group->pluck('application_id')->map(fn ($id) => (int) $id)->all();

            foreach ($appIds as $appId) {
                if (in_array((int) $appId, $existing, true)) {
                    continue;
                }

                $this->insertAnswerCopy($sample, (int) $appId);
            }
        }

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

    private function applicationIdsForType(int $typeId)
    {
        return DB::table('applications')
            ->where('application_type_id', $typeId)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->pluck('id');
    }

    private function spreadAnswerToTypeApplications(object $answer): void
    {
        $appIds = $this->applicationIdsForType((int) $answer->application_type_id);

        if ($appIds->isEmpty()) {
            DB::table('application_answers')->where('id', $answer->id)->delete();

            return;
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

            $exists = DB::table('application_answers')
                ->where('question_id', $answer->question_id)
                ->where('application_id', $appId)
                ->exists();

            if (! $exists) {
                $this->insertAnswerCopy($answer, (int) $appId);
            }
        }
    }

    private function insertAnswerCopy(object $answer, int $applicationId): void
    {
        DB::table('application_answers')->insert([
            'question_id' => $answer->question_id,
            'application_type_id' => $answer->application_type_id,
            'application_id' => $applicationId,
            'value' => $answer->value,
            'details' => $answer->details ?? null,
            'answered_by_id' => $answer->answered_by_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
