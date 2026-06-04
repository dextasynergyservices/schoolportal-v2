<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_access_resets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->timestamp('available_from');
            $table->timestamp('available_until');
            $table->timestamp('used_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'student_id', 'available_until'], 'idx_exam_reset_student_window');
            $table->unique('attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_access_resets');
    }
};
