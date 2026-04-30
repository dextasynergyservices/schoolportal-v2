<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Do the tricky student_term_reports changes FIRST via raw SQL to avoid partial state
        // MySQL needs all FK/index changes in a single ALTER TABLE to avoid constraint conflicts
        DB::statement('
            ALTER TABLE student_term_reports
                ADD INDEX tmp_student_id_idx (student_id),
                DROP FOREIGN KEY student_term_reports_term_id_foreign,
                DROP INDEX unique_student_session_term
        ');

        DB::statement('
            ALTER TABLE student_term_reports
                MODIFY term_id BIGINT UNSIGNED NULL,
                ADD COLUMN report_type ENUM("midterm","full_term","session") NOT NULL DEFAULT "full_term" AFTER term_id,
                ADD CONSTRAINT student_term_reports_term_id_foreign FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
                ADD UNIQUE INDEX unique_student_session_term_type (student_id, session_id, term_id, report_type),
                DROP INDEX tmp_student_id_idx
        ');

        // Simple column additions - safe to use Schema builder
        Schema::table('score_components', function (Blueprint $table) {
            $table->boolean('include_in_midterm')->default(true)->after('is_active');
        });

        Schema::table('report_card_config', function (Blueprint $table) {
            $table->json('enabled_report_types')->nullable()->after('principal_title');
            $table->enum('session_calculation_method', ['average_of_terms', 'weighted_average', 'best_two_of_three'])
                ->default('average_of_terms')
                ->after('enabled_report_types');
            $table->decimal('midterm_weight', 5, 2)->nullable()->after('session_calculation_method');
            $table->decimal('fullterm_weight', 5, 2)->nullable()->after('midterm_weight');
            $table->boolean('show_term_breakdown_in_session')->default(true)->after('fullterm_weight');
        });
    }

    public function down(): void
    {
        Schema::table('report_card_config', function (Blueprint $table) {
            $table->dropColumn([
                'enabled_report_types',
                'session_calculation_method',
                'midterm_weight',
                'fullterm_weight',
                'show_term_breakdown_in_session',
            ]);
        });

        Schema::table('score_components', function (Blueprint $table) {
            $table->dropColumn('include_in_midterm');
        });

        DB::statement('
            ALTER TABLE student_term_reports
                ADD INDEX tmp_student_id_idx (student_id),
                DROP FOREIGN KEY student_term_reports_term_id_foreign,
                DROP INDEX unique_student_session_term_type
        ');

        DB::statement('
            ALTER TABLE student_term_reports
                DROP COLUMN report_type,
                MODIFY term_id BIGINT UNSIGNED NOT NULL,
                ADD CONSTRAINT student_term_reports_term_id_foreign FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
                ADD UNIQUE INDEX unique_student_session_term (student_id, session_id, term_id),
                DROP INDEX tmp_student_id_idx
        ');
    }
};
