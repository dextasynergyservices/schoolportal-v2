<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('source_type', ['document', 'prompt', 'manual']);
            $table->string('source_document_url', 500)->nullable();
            $table->string('source_document_public_id')->nullable();
            $table->text('source_prompt')->nullable();
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('passing_score')->default(50);
            $table->integer('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->integer('total_questions')->default(0);
            $table->timestamps();

            $table->index(['school_id', 'class_id']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'is_published']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['multiple_choice', 'true_false', 'fill_blank'])->default('multiple_choice');
            $table->text('question_text');
            $table->string('question_image_url', 500)->nullable();
            $table->json('options');
            $table->string('correct_answer', 500);
            $table->text('explanation')->nullable();
            $table->integer('points')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('quiz_id');
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->integer('attempt_number')->default(1);
            $table->integer('score')->nullable();
            $table->integer('total_points')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'timed_out'])->default('in_progress');

            $table->unique(['quiz_id', 'student_id', 'attempt_number'], 'unique_attempt');
            $table->index('student_id');
            $table->index(['quiz_id', 'status']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('selected_answer', 500)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('points_earned')->default(0);
            $table->timestamp('answered_at')->useCurrent();

            $table->unique(['attempt_id', 'question_id'], 'unique_answer');
            $table->index('attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
