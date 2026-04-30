<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the ENUM to include 'theory', update existing rows, then remove 'essay'
        DB::statement("ALTER TABLE exam_questions MODIFY COLUMN `type` ENUM('multiple_choice','true_false','fill_blank','short_answer','essay','theory','matching') NOT NULL DEFAULT 'multiple_choice'");
        DB::statement("UPDATE exam_questions SET `type` = 'theory' WHERE `type` = 'essay'");
        DB::statement("ALTER TABLE exam_questions MODIFY COLUMN `type` ENUM('multiple_choice','true_false','fill_blank','short_answer','theory','matching') NOT NULL DEFAULT 'multiple_choice'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE exam_questions MODIFY COLUMN `type` ENUM('multiple_choice','true_false','fill_blank','short_answer','theory','essay','matching') NOT NULL DEFAULT 'multiple_choice'");
        DB::statement("UPDATE exam_questions SET `type` = 'essay' WHERE `type` = 'theory'");
        DB::statement("ALTER TABLE exam_questions MODIFY COLUMN `type` ENUM('multiple_choice','true_false','fill_blank','short_answer','essay','matching') NOT NULL DEFAULT 'multiple_choice'");
    }
};
