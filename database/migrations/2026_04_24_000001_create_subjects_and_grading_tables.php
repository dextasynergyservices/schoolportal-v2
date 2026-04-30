<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Subjects ──
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);             // e.g. "Mathematics", "English Language"
            $table->string('slug', 100);
            $table->string('short_name', 20)->nullable(); // e.g. "MATH", "ENG"
            $table->string('category', 50)->nullable();   // e.g. "Science", "Arts", "Commercial"
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['school_id', 'slug']);
            $table->index('school_id');
        });

        // ── Class-Subject pivot (which subjects are taught in which class, with optional subject teacher) ──
        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete(); // subject teacher

            $table->unique(['class_id', 'subject_id']);
            $table->index('school_id');
            $table->index('teacher_id');
        });

        // ── Grading Scales (e.g. "Nigerian Standard", "Custom Scale") ──
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);              // e.g. "Standard Grading"
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('school_id');
        });

        // ── Grading Scale Items (A=70-100, B=60-69, etc.) ──
        Schema::create('grading_scale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('grade', 5);                // "A1", "B2", "A", "F"
            $table->string('label', 50);               // "Excellent", "Very Good", "Fail"
            $table->unsignedTinyInteger('min_score');   // 70
            $table->unsignedTinyInteger('max_score');   // 100
            $table->unsignedTinyInteger('sort_order')->default(0);

            $table->index('grading_scale_id');
            $table->index('school_id');
        });

        // ── Score Components (CA1, CA2, Exam, etc. — school defines their own) ──
        Schema::create('score_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);                // "CA1", "CA2", "Mid-Term Test", "Exam"
            $table->string('short_name', 10);          // "CA1", "MID", "EXM"
            $table->unsignedTinyInteger('max_score');   // max marks for this component (e.g. 10, 20, 70)
            $table->unsignedTinyInteger('weight');      // percentage weight (all must sum to 100)
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('school_id');
        });

        // ── Report Card Configuration (psychomotor/affective traits, comments, display options) ──
        Schema::create('report_card_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->json('psychomotor_traits')->nullable();   // ["Punctuality", "Neatness", "Handwriting", ...]
            $table->json('affective_traits')->nullable();     // ["Honesty", "Obedience", "Politeness", ...]
            $table->json('trait_rating_scale')->nullable();    // [{"value": 5, "label": "Excellent"}, {"value": 4, "label": "Very Good"}, ...]
            $table->json('comment_presets')->nullable();       // {"excellent": ["Excellent performance..."], "good": ["Good work..."], ...}
            $table->boolean('show_position')->default(true);
            $table->boolean('show_class_average')->default(true);
            $table->boolean('show_subject_teacher')->default(false);
            $table->boolean('show_grade_summary')->default(true);
            $table->boolean('require_class_teacher_comment')->default(true);
            $table->boolean('require_principal_comment')->default(true);
            $table->string('principal_title', 50)->default('Principal');  // "Principal", "Head Teacher", "Proprietress"
            $table->timestamps();

            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_config');
        Schema::dropIfExists('score_components');
        Schema::dropIfExists('grading_scale_items');
        Schema::dropIfExists('grading_scales');
        Schema::dropIfExists('class_subject');
        Schema::dropIfExists('subjects');
    }
};
