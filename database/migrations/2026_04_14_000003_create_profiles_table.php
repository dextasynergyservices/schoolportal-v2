<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->string('admission_number', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('medical_notes')->nullable();
            $table->foreignId('enrolled_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();

            $table->index(['school_id', 'class_id']);
        });

        Schema::create('parent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('occupation')->nullable();
            $table->enum('relationship', ['father', 'mother', 'guardian', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            $table->unique(['parent_id', 'student_id']);
            $table->index('parent_id');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('parent_profiles');
        Schema::dropIfExists('student_profiles');
    }
};
