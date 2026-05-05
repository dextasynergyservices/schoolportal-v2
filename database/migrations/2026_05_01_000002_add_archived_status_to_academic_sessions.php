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
        // Add 'archived' value to the status enum.
        // MySQL requires re-declaring the full ENUM to add a value.
        DB::statement(
            "ALTER TABLE academic_sessions MODIFY COLUMN status
            ENUM('upcoming','active','completed','archived') DEFAULT 'upcoming'"
        );

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        // Revert enum to original values (rows with 'archived' should be changed first).
        DB::statement(
            "ALTER TABLE academic_sessions MODIFY COLUMN status
            ENUM('upcoming','active','completed') DEFAULT 'upcoming'"
        );
    }
};
