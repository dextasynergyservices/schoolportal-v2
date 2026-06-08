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
        Schema::create('grading_scale_level', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grading_scale_id')->constrained('grading_scales')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('school_levels')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['school_id', 'level_id'], 'grading_scale_level_unique_level');
            $table->unique(['grading_scale_id', 'level_id'], 'grading_scale_level_unique_scale_level');
            $table->index(['school_id', 'grading_scale_id'], 'grading_scale_level_school_scale_index');
        });

        $this->ensureSingleDefaultScalePerSchool();
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scale_level');
    }

    private function ensureSingleDefaultScalePerSchool(): void
    {
        $schoolIds = DB::table('grading_scales')
            ->select('school_id')
            ->distinct()
            ->pluck('school_id');

        foreach ($schoolIds as $schoolId) {
            $defaultScale = DB::table('grading_scales')
                ->where('school_id', $schoolId)
                ->where('is_default', true)
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            $defaultScale ??= DB::table('grading_scales')
                ->where('school_id', $schoolId)
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            if (! $defaultScale) {
                continue;
            }

            DB::table('grading_scales')
                ->where('school_id', $schoolId)
                ->update(['is_default' => false]);

            DB::table('grading_scales')
                ->where('id', $defaultScale->id)
                ->update(['is_default' => true]);
        }
    }
};
