<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolSetupService
{
    /** @var array<string, array<int, string>> */
    public const LEVEL_PRESETS = [
        'nursery' => ['Nursery 1', 'Nursery 2', 'Nursery 3'],
        'primary' => ['Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6'],
        'jss' => ['JSS 1', 'JSS 2', 'JSS 3'],
        'sss' => ['SSS 1', 'SSS 2', 'SSS 3'],
    ];

    /** @var array<string, string> */
    public const LEVEL_NAMES = [
        'nursery' => 'Nursery',
        'primary' => 'Primary',
        'jss' => 'Junior Secondary',
        'sss' => 'Senior Secondary',
    ];

    /**
     * Create a school end-to-end: school record, levels, classes, first admin,
     * first academic session and its three terms — all in a single transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): School
    {
        return DB::transaction(function () use ($data): School {
            $school = School::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'custom_domain' => $data['custom_domain'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? 'Nigeria',
                'website' => $data['website'] ?? null,
                'motto' => $data['motto'] ?? null,
                'ai_free_credits' => (int) PlatformSetting::get('default_free_ai_credits', 15),
                'ai_purchased_credits' => 0,
                'ai_free_credits_reset_at' => now()->addMonth()->startOfMonth()->toDateString(),
                'ai_credits_total_purchased' => 0,
                'is_active' => true,
                'settings' => [
                    'branding' => [
                        'primary_color' => '#4F46E5',
                        'secondary_color' => '#F59E0B',
                        'accent_color' => '#10B981',
                    ],
                    'portal' => [
                        'enable_parent_portal' => true,
                        'enable_quiz_generator' => true,
                        'enable_game_generator' => true,
                        'enable_teacher_approval' => true,
                        'session_timeout_minutes' => 30,
                        'max_file_upload_mb' => 10,
                    ],
                    'academic' => [
                        'grading_system' => 'percentage',
                        'terms_per_session' => 3,
                        'weeks_per_term' => 12,
                    ],
                ],
            ]);

            $levels = $this->createLevels($school, $data['levels'] ?? []);
            $this->createClasses($school, $levels, $data['classes'] ?? []);
            $this->createAdmin($school, $data);
            $this->createSession($school, $data);

            return $school;
        });
    }

    /**
     * @param  array<int, string>  $slugs  e.g. ['nursery', 'primary']
     * @return array<string, SchoolLevel> keyed by slug
     */
    private function createLevels(School $school, array $slugs): array
    {
        $levels = [];
        $sortOrder = 0;

        foreach ($slugs as $slug) {
            if (! array_key_exists($slug, self::LEVEL_NAMES)) {
                continue;
            }

            $levels[$slug] = SchoolLevel::create([
                'school_id' => $school->id,
                'name' => self::LEVEL_NAMES[$slug],
                'slug' => $slug,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
        }

        return $levels;
    }

    /**
     * @param  array<string, SchoolLevel>  $levels
     * @param  array<string, array<int, string>>  $customClasses
     */
    private function createClasses(School $school, array $levels, array $customClasses): void
    {
        foreach ($levels as $slug => $level) {
            $names = $customClasses[$slug] ?? self::LEVEL_PRESETS[$slug] ?? [];
            $sortOrder = 0;

            foreach ($names as $name) {
                $name = trim($name);
                if ($name === '') {
                    continue;
                }

                SchoolClass::create([
                    'school_id' => $school->id,
                    'level_id' => $level->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createAdmin(School $school, array $data): User
    {
        return User::withoutEvents(fn () => User::create([
            'school_id' => $school->id,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'username' => $data['admin_username'],
            'password' => Hash::make($data['admin_password']),
            'role' => 'school_admin',
            'phone' => $data['admin_phone'] ?? null,
            'is_active' => true,
            'must_change_password' => true,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createSession(School $school, array $data): AcademicSession
    {
        $session = AcademicSession::create([
            'school_id' => $school->id,
            'name' => $data['session_name'],
            'start_date' => $data['session_start_date'],
            'end_date' => $data['session_end_date'],
            'is_current' => true,
            'status' => 'active',
        ]);

        $termNames = ['First Term', 'Second Term', 'Third Term'];

        foreach ($termNames as $i => $name) {
            Term::create([
                'school_id' => $school->id,
                'session_id' => $session->id,
                'term_number' => $i + 1,
                'name' => $name,
                'is_current' => $i === 0,
                'status' => $i === 0 ? 'active' : 'upcoming',
            ]);
        }

        return $session;
    }

    /**
     * Adjust a school's AI credit balance (super admin only).
     */
    public function adjustCredits(School $school, int $freeDelta, int $purchasedDelta): void
    {
        $school->update([
            'ai_free_credits' => max(0, $school->ai_free_credits + $freeDelta),
            'ai_purchased_credits' => max(0, $school->ai_purchased_credits + $purchasedDelta),
        ]);
    }
}
