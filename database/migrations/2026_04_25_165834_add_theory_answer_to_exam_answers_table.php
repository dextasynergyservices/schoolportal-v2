<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->text('theory_answer')->nullable()->after('selected_answer');
        });

        // Migrate existing theory answers from selected_answer to theory_answer
        DB::table('exam_answers')
            ->join('exam_questions', 'exam_answers.question_id', '=', 'exam_questions.id')
            ->whereIn('exam_questions.type', ['theory', 'short_answer'])
            ->whereNotNull('exam_answers.selected_answer')
            ->update([
                'exam_answers.theory_answer' => DB::raw('exam_answers.selected_answer'),
                'exam_answers.selected_answer' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move theory answers back to selected_answer
        DB::table('exam_answers')
            ->whereNotNull('theory_answer')
            ->update([
                'selected_answer' => DB::raw('theory_answer'),
            ]);

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('theory_answer');
        });
    }
};
