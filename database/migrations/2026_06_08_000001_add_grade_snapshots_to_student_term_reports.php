<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_term_reports', function (Blueprint $table) {
            $table->foreignId('grading_scale_id')
                ->nullable()
                ->after('subject_scores_snapshot')
                ->constrained('grading_scales')
                ->nullOnDelete();
            $table->json('grading_scale_snapshot')->nullable()->after('grading_scale_id');
            $table->string('overall_grade', 20)->nullable()->after('average_weighted_score');
            $table->string('overall_grade_label', 100)->nullable()->after('overall_grade');
        });
    }

    public function down(): void
    {
        Schema::table('student_term_reports', function (Blueprint $table) {
            $table->dropForeign(['grading_scale_id']);
            $table->dropColumn([
                'grading_scale_id',
                'grading_scale_snapshot',
                'overall_grade',
                'overall_grade_label',
            ]);
        });
    }
};
