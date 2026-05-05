<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes to performance-critical tables.
 *
 * These supplement the existing single/two-column indexes with wider composites
 * that cover the most common multi-filter queries (school + session/term/class/status).
 *
 * Tables covered:
 *  - results          → idx_results_scope
 *  - exam_attempts    → idx_attempts_scope
 *  - audit_logs       → idx_audit_time  (school_id + created_at for date-filtered audit log viewer)
 *
 * Note: student_subject_scores already has a UNIQUE constraint on
 * (student_id, subject_id, term_id, score_component_id) which MySQL implicitly
 * indexes, and idx_class_subject_term (school_id, class_id, subject_id, term_id)
 * covers the primary lookup pattern — no new index needed there.
 */
return new class extends Migration
{
    public function up(): void
    {
        // results: wider composite covering the most frequent admin/teacher filter
        // (show all results for a class in a given session+term with a given status)
        Schema::table('results', function (Blueprint $table) {
            $table->index(
                ['school_id', 'session_id', 'term_id', 'class_id', 'status'],
                'idx_results_scope'
            );
        });

        // exam_attempts: covers student-level attempt lookups filtered by exam + status
        // (e.g. "fetch this student's attempts on this exam where status = submitted")
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->index(
                ['school_id', 'student_id', 'exam_id', 'status'],
                'idx_attempts_scope'
            );
        });

        // audit_logs: the audit log viewer filters by school_id + date range;
        // the existing (school_id, action) and separate (created_at) indexes
        // can't satisfy both predicates in one scan — this composite does.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(
                ['school_id', 'created_at'],
                'idx_audit_time'
            );
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_time');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_attempts_scope');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex('idx_results_scope');
        });
    }
};
