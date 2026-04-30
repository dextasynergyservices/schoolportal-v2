<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-student, per-subject, per-component scores for the term
        Schema::create('student_subject_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('score_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable(); // actual score (normalized to component max)
            $table->integer('max_score'); // component max_score
            $table->enum('source_type', ['cbt', 'manual'])->default('cbt');
            $table->foreignId('source_exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->foreignId('source_attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'term_id', 'score_component_id'], 'unique_student_subject_component_term');
            $table->index(['school_id', 'class_id', 'subject_id', 'term_id'], 'idx_class_subject_term');
        });

        // Per-student term report (report card data)
        Schema::create('student_term_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();

            // Snapshot of all subject scores (for PDF generation)
            $table->json('subject_scores_snapshot')->nullable();

            // Overall aggregates
            $table->decimal('total_weighted_score', 8, 2)->nullable();
            $table->decimal('average_weighted_score', 8, 2)->nullable();
            $table->integer('subjects_count')->default(0);
            $table->integer('position')->nullable();
            $table->integer('out_of')->nullable();

            // Behavioural ratings
            $table->json('psychomotor_ratings')->nullable();
            $table->json('affective_ratings')->nullable();

            // Attendance
            $table->integer('attendance_present')->nullable();
            $table->integer('attendance_absent')->nullable();
            $table->integer('attendance_total')->nullable();

            // Comments
            $table->text('teacher_comment')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('principal_comment')->nullable();

            // Signature
            $table->string('signature_url', 500)->nullable();
            $table->string('signature_public_id', 255)->nullable();

            // Workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'published'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'session_id', 'term_id'], 'unique_student_session_term');
            $table->index(['school_id', 'class_id', 'term_id', 'status'], 'idx_class_term_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_term_reports');
        Schema::dropIfExists('student_subject_scores');
    }
};
