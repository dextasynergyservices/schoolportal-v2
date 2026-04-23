<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unsignedInteger('login_streak')->default(0)->after('enrolled_at');
            $table->unsignedInteger('best_login_streak')->default(0)->after('login_streak');
            $table->date('last_streak_date')->nullable()->after('best_login_streak');
            $table->unsignedInteger('quiz_pass_streak')->default(0)->after('last_streak_date');
            $table->unsignedInteger('best_quiz_pass_streak')->default(0)->after('quiz_pass_streak');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'login_streak',
                'best_login_streak',
                'last_streak_date',
                'quiz_pass_streak',
                'best_quiz_pass_streak',
            ]);
        });
    }
};
