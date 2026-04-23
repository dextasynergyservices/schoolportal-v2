<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('achievement_key', 50);
            $table->timestamp('unlocked_at')->useCurrent();
            $table->json('metadata')->nullable();

            $table->unique(['student_id', 'achievement_key'], 'unique_student_achievement');
            $table->index('school_id', 'idx_school');
            $table->index('student_id', 'idx_student');
            $table->index(['student_id', 'unlocked_at'], 'idx_student_unlocked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
