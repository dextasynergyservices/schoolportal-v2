<?php

declare(strict_types=1);

use App\Models\QuestionBank;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->enum('type', [
                'multiple_choice',
                'true_false',
                'fill_blank',
                'short_answer',
                'theory',
                'matching',
            ])->default('multiple_choice');

            $table->text('question_text');
            $table->string('question_image_url', 500)->nullable();
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->text('marking_guide')->nullable();
            $table->text('sample_answer')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->unsignedSmallInteger('min_words')->nullable();
            $table->unsignedSmallInteger('max_words')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('tags')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();

            $table->index('school_id');
            $table->index(['school_id', 'subject_id']);
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'difficulty']);
        });

        // Track which bank question an exam_question was imported from
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('question_bank_id')
                ->nullable()
                ->after('school_id')
                ->constrained('question_bank')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeignIdFor(QuestionBank::class);
            $table->dropColumn('question_bank_id');
        });

        Schema::dropIfExists('question_bank');
    }
};
