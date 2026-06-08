<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;

class GradingScaleResolver
{
    public function resolveForStudent(User $student): ?GradingScale
    {
        $schoolId = (int) $student->school_id;
        $levelId = $this->resolveStudentLevelId($student);

        return $levelId !== null
            ? $this->resolveForLevel($levelId, $schoolId)
            : $this->resolveDefaultForSchool($schoolId);
    }

    public function resolveForLevel(?int $levelId, int $schoolId): ?GradingScale
    {
        if ($levelId !== null) {
            $scale = GradingScale::withoutGlobalScopes()
                ->select('grading_scales.*')
                ->join('grading_scale_level', 'grading_scale_level.grading_scale_id', '=', 'grading_scales.id')
                ->where('grading_scale_level.school_id', $schoolId)
                ->where('grading_scale_level.level_id', $levelId)
                ->where('grading_scales.school_id', $schoolId)
                ->where('grading_scales.is_active', true)
                ->first();

            if ($scale) {
                return $scale;
            }
        }

        return $this->resolveDefaultForSchool($schoolId);
    }

    public function resolveDefaultForSchool(int $schoolId): ?GradingScale
    {
        return GradingScale::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function getGrade(int $schoolId, float $percentage, ?int $levelId = null): ?array
    {
        $scale = $this->resolveForLevel($levelId, $schoolId);

        if (! $scale) {
            return null;
        }

        $item = GradingScaleItem::withoutGlobalScopes()
            ->where('grading_scale_id', $scale->id)
            ->where('school_id', $schoolId)
            ->where('min_score', '<=', $percentage)
            ->where('max_score', '>=', $percentage)
            ->first();

        if (! $item) {
            return null;
        }

        return [
            'grade' => $item->grade,
            'label' => $item->label,
            'grading_scale_id' => $scale->id,
        ];
    }

    private function resolveStudentLevelId(User $student): ?int
    {
        $profile = $student->relationLoaded('studentProfile')
            ? $student->studentProfile
            : StudentProfile::withoutGlobalScopes()
                ->where('user_id', $student->id)
                ->where('school_id', $student->school_id)
                ->first();

        if ($profile?->relationLoaded('class')) {
            return $profile->class?->level_id;
        }

        if ($profile?->class_id) {
            $levelId = SchoolClass::withoutGlobalScopes()
                ->where('school_id', $student->school_id)
                ->whereKey($profile->class_id)
                ->value('level_id');

            if ($levelId !== null) {
                return (int) $levelId;
            }
        }

        return $student->level_id ? (int) $student->level_id : null;
    }
}
