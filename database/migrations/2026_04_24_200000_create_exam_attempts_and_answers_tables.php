<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('total_points')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'timed_out', 'grading'])->default('in_progress');
            $table->unsignedSmallInteger('tab_switches')->default(0);
            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'attempt_number'], 'unique_exam_attempt');
            $table->index('student_id', 'idx_student');
            $table->index(['exam_id', 'status'], 'idx_exam_status');
            $table->index(['school_id', 'student_id'], 'idx_school_student');
        });

        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->text('selected_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('points_earned')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();

            $table->unique(['attempt_id', 'question_id'], 'unique_attempt_answer');
            $table->index('attempt_id', 'idx_attempt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
    }
};
