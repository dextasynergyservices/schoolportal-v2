<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('game_type', ['memory_match', 'word_scramble', 'quiz_race', 'flashcard']);
            $table->enum('source_type', ['document', 'prompt', 'manual']);
            $table->string('source_document_url', 500)->nullable();
            $table->string('source_document_public_id')->nullable();
            $table->text('source_prompt')->nullable();
            $table->json('game_data');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('time_limit_minutes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->timestamps();

            $table->index(['school_id', 'class_id']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'is_published']);
        });

        Schema::create('game_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->integer('score')->nullable();
            $table->integer('max_score')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->boolean('completed')->default(false);
            $table->json('game_state')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->index('game_id');
            $table->index('student_id');
            $table->index(['game_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_plays');
        Schema::dropIfExists('games');
    }
};
