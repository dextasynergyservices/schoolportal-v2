<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exams CHANGE COLUMN exam_type category ENUM('assessment','exam') NOT NULL DEFAULT 'exam'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exams CHANGE COLUMN category exam_type ENUM('cbt','theory','mixed') NOT NULL DEFAULT 'cbt'");
    }
};
