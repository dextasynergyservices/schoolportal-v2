<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exam_attempts MODIFY COLUMN status ENUM('in_progress','submitted','timed_out','grading','graded','grading_failed') NOT NULL DEFAULT 'in_progress'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exam_attempts MODIFY COLUMN status ENUM('in_progress','submitted','timed_out','grading','graded') NOT NULL DEFAULT 'in_progress'");
    }
};
