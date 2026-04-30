<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('score_component_id')->nullable()->constrained('score_components')->nullOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('exam_type', ['cbt', 'theory', 'mixed'])->default('cbt');

            // Source (AI generation)
            $table->enum('source_type', ['prompt', 'document', 'manual'])->default('manual');
            $table->text('source_prompt')->nullable();
            $table->string('source_document_url', 500)->nullable();
            $table->string('source_document_public_id', 255)->nullable();

            // Settings
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('max_score')->default(100);
            $table->integer('passing_score')->default(40); // percentage
            $table->integer('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('show_correct_answers')->default(false); // more restrictive for exams
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');

            // Availability window
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();

            // Anti-cheating
            $table->boolean('prevent_tab_switch')->default(true);
            $table->boolean('prevent_copy_paste')->default(true);
            $table->boolean('randomize_per_student')->default(false);
            $table->integer('max_tab_switches')->default(3); // auto-submit after N switches

            // Publishing & approval
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');

            $table->integer('total_questions')->default(0);
            $table->integer('total_points')->default(0);
            $table->text('instructions')->nullable(); // shown to students before starting
            $table->timestamps();

            $table->index(['school_id', 'class_id']);
            $table->index(['school_id', 'subject_id']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'is_published']);
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->enum('type', [
                'multiple_choice',
                'true_false',
                'fill_blank',
                'short_answer',
                'essay',
                'matching',
            ])->default('multiple_choice');

            $table->text('question_text');
            $table->string('question_image_url', 500)->nullable();
            $table->string('question_image_public_id', 255)->nullable();

            // Options (JSON) — used by MCQ, true_false, matching
            // MCQ: ["Option A", "Option B", "Option C", "Option D"]
            // true_false: ["True", "False"]
            // matching: [{"left": "Term", "right": "Definition"}, ...]
            $table->json('options')->nullable();

            // Correct answer — text for MCQ/TF/fill_blank/short_answer, JSON for matching
            $table->text('correct_answer')->nullable();

            // For essay/short_answer: marking guide and sample answer
            $table->text('marking_guide')->nullable();
            $table->text('sample_answer')->nullable();

            // For essay: word limits
            $table->integer('min_words')->nullable();
            $table->integer('max_words')->nullable();

            $table->text('explanation')->nullable();
            $table->integer('points')->default(1);
            $table->integer('sort_order')->default(0);

            // Section grouping (optional — e.g., "Section A: Objectives", "Section B: Theory")
            $table->string('section_label', 100)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('exam_id');
            $table->index(['exam_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
    }
};
