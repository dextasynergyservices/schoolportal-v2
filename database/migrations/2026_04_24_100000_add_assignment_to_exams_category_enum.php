<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exams MODIFY COLUMN category ENUM('assessment','assignment','exam') NOT NULL DEFAULT 'exam'");
    }

    public function down(): void
    {
        // Move any 'assignment' rows to 'assessment' before shrinking the ENUM
        DB::table('exams')->where('category', 'assignment')->update(['category' => 'assessment']);
        DB::statement("ALTER TABLE exams MODIFY COLUMN category ENUM('assessment','exam') NOT NULL DEFAULT 'exam'");
    }
};
