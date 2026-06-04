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
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->string('completion_reason', 30)->nullable()->after('status');
        });

        DB::table('exam_attempts')
            ->where('status', 'timed_out')
            ->whereNull('completion_reason')
            ->update(['completion_reason' => 'time_elapsed']);
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn('completion_reason');
        });
    }
};
