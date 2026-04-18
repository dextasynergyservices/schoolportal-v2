<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\Notice;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\StudentProfile;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Migrates legacy Dexta Schools data from the old PHP portal.
 *
 * Prerequisites:
 * 1. Add legacy_nursery and legacy_primary DB connections to config/database.php
 * 2. Add LEGACY_NURSERY_DB_* and LEGACY_PRIMARY_DB_* to .env
 * 3. Ensure the new platform's migrations have been run (php artisan migrate)
 *
 * Usage:
 *   php artisan db:seed --class=MigrateLegacyDataSeeder
 *
 * See docs/DATA_MIGRATION_GUIDE.md for full instructions.
 */
class MigrateLegacyDataSeeder extends Seeder
{
    /**
     * Temporary password for all migrated users.
     * They will be forced to change it on first login.
     */
    private string $tempPassword;

    /**
     * Mapping of old class IDs to new class IDs.
     * Keyed by "{level}_{old_id}" to avoid collisions between nursery and primary.
     */
    private array $classIdMap = [];

    /**
     * Mapping of old session IDs to new session IDs.
     */
    private array $sessionIdMap = [];

    /**
     * Mapping of old term IDs to new term IDs.
     */
    private array $termIdMap = [];

    /**
     * The newly created school record.
     */
    private School $school;

    /**
     * School level records.
     */
    private SchoolLevel $nurseryLevel;

    private SchoolLevel $primaryLevel;

    public function run(): void
    {
        $this->tempPassword = Hash::make('changeme123');

        $this->command->info('Starting legacy data migration...');
        $this->command->newLine();

        DB::transaction(function () {
            $this->createSchool();
            $this->createSchoolLevels();
            $this->migrateAcademicSessions();
            $this->migrateClasses();
            $this->migrateAdmins();
            $this->migrateStudents();
            $this->migrateNotices();
            $this->migrateAssignments();
        });

        $this->command->newLine();
        $this->command->info('Migration complete! Verify data with: php artisan tinker');
        $this->command->warn('All migrated users must change their password on first login.');
        $this->command->warn('Temporary password: changeme123');
        $this->printSummary();
    }

    /**
     * Step 1: Create the school record.
     */
    private function createSchool(): void
    {
        $this->command->info('Creating school...');

        $this->school = School::firstOrCreate(
            ['slug' => 'dexta-schools'],
            [
                'name' => 'Dexta Schools',
                'slug' => 'dexta-schools',
                'email' => 'info@dextaschools.com', // Update with real email
                'phone' => null,
                'address' => null, // Update with real address
                'city' => null,
                'state' => null,
                'country' => 'Nigeria',
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
            ]
        );

        $this->command->info("  School created: {$this->school->name} (ID: {$this->school->id})");
    }

    /**
     * Step 2: Create school levels (Nursery + Primary).
     */
    private function createSchoolLevels(): void
    {
        $this->command->info('Creating school levels...');

        $this->nurseryLevel = SchoolLevel::firstOrCreate(
            ['school_id' => $this->school->id, 'slug' => 'nursery'],
            [
                'name' => 'Nursery',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->primaryLevel = SchoolLevel::firstOrCreate(
            ['school_id' => $this->school->id, 'slug' => 'primary'],
            [
                'name' => 'Primary',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $this->command->info("  Nursery level ID: {$this->nurseryLevel->id}");
        $this->command->info("  Primary level ID: {$this->primaryLevel->id}");
    }

    /**
     * Step 3: Migrate academic sessions and terms.
     * Only the nursery DB has these tables (added later). We use them for both levels.
     */
    private function migrateAcademicSessions(): void
    {
        $this->command->info('Migrating academic sessions...');

        $oldSessions = DB::connection('legacy_nursery')
            ->table('academic_sessions')
            ->orderBy('id')
            ->get();

        if ($oldSessions->isEmpty()) {
            $this->command->warn('  No academic sessions found. Creating a default 2024/2025 session.');

            $session = AcademicSession::firstOrCreate(
                ['school_id' => $this->school->id, 'name' => '2024/2025'],
                [
                    'start_date' => '2024-09-08',
                    'end_date' => '2025-07-31',
                    'is_current' => false,
                    'status' => 'completed',
                ]
            );
            $this->sessionIdMap[1] = $session->id;

            // Create 3 terms for this session
            $this->createTermsForSession($session);

            return;
        }

        foreach ($oldSessions as $old) {
            $status = match ($old->status) {
                'archived' => 'completed',
                default => $old->status,
            };

            $session = AcademicSession::firstOrCreate(
                ['school_id' => $this->school->id, 'name' => $old->session_name],
                [
                    'start_date' => $old->start_date,
                    'end_date' => $old->end_date,
                    'is_current' => (bool) $old->is_current,
                    'status' => $status,
                ]
            );

            $this->sessionIdMap[$old->id] = $session->id;
            $this->command->info("  Session: {$old->session_name} → ID {$session->id}");

            // Migrate terms for this session
            $this->migrateTermsForSession($old->id, $session);
        }
    }

    /**
     * Migrate terms from legacy DB for a specific session.
     */
    private function migrateTermsForSession(int $oldSessionId, AcademicSession $session): void
    {
        $oldTerms = DB::connection('legacy_nursery')
            ->table('terms')
            ->where('session_id', $oldSessionId)
            ->orderBy('term_number')
            ->get();

        if ($oldTerms->isEmpty()) {
            $this->createTermsForSession($session);

            return;
        }

        foreach ($oldTerms as $old) {
            $term = Term::firstOrCreate(
                ['school_id' => $this->school->id, 'session_id' => $session->id, 'term_number' => $old->term_number],
                [
                    'name' => $old->term_name,
                    'start_date' => $old->start_date,
                    'end_date' => $old->end_date,
                    'is_current' => (bool) $old->is_current,
                    'status' => $old->status ?? 'upcoming',
                ]
            );

            $this->termIdMap[$old->id] = $term->id;
        }
    }

    /**
     * Create default 3 terms for a session if none exist in legacy DB.
     */
    private function createTermsForSession(AcademicSession $session): void
    {
        $termNames = ['First Term', 'Second Term', 'Third Term'];

        foreach ($termNames as $i => $name) {
            $term = Term::firstOrCreate(
                ['school_id' => $this->school->id, 'session_id' => $session->id, 'term_number' => $i + 1],
                [
                    'name' => $name,
                    'is_current' => false,
                    'status' => 'completed',
                ]
            );

            // Use a synthetic old ID mapping (session_id * 10 + term_number)
            // so we can map old term references
            $syntheticOldId = $session->id * 10 + ($i + 1);
            $this->termIdMap[$syntheticOldId] = $term->id;
        }
    }

    /**
     * Step 4: Migrate classes from both databases.
     */
    private function migrateClasses(): void
    {
        $this->command->info('Migrating classes...');

        // Nursery classes
        $nurseryClasses = DB::connection('legacy_nursery')
            ->table('classes')
            ->orderBy('id')
            ->get();

        foreach ($nurseryClasses as $old) {
            $class = SchoolClass::firstOrCreate(
                ['school_id' => $this->school->id, 'level_id' => $this->nurseryLevel->id, 'slug' => Str::slug($old->class_name)],
                [
                    'name' => $old->class_name,
                    'sort_order' => $old->id,
                    'is_active' => true,
                ]
            );

            $this->classIdMap["nursery_{$old->id}"] = $class->id;
        }

        $this->command->info("  Nursery classes: {$nurseryClasses->count()}");

        // Primary classes
        $primaryClasses = DB::connection('legacy_primary')
            ->table('classes')
            ->orderBy('id')
            ->get();

        foreach ($primaryClasses as $old) {
            $class = SchoolClass::firstOrCreate(
                ['school_id' => $this->school->id, 'level_id' => $this->primaryLevel->id, 'slug' => Str::slug($old->class_name)],
                [
                    'name' => $old->class_name,
                    'sort_order' => $old->id,
                    'is_active' => true,
                ]
            );

            $this->classIdMap["primary_{$old->id}"] = $class->id;
        }

        $this->command->info("  Primary classes: {$primaryClasses->count()}");
    }

    /**
     * Step 5: Migrate admin users from both databases.
     * De-duplicates by username (keeps first occurrence).
     */
    private function migrateAdmins(): void
    {
        $this->command->info('Migrating admin users...');

        $seenUsernames = [];
        $count = 0;

        foreach (['legacy_nursery', 'legacy_primary'] as $connection) {
            $admins = DB::connection($connection)->table('admin')->get();

            foreach ($admins as $admin) {
                $username = Str::lower(trim($admin->username));

                // Skip duplicates
                if (isset($seenUsernames[$username])) {
                    continue;
                }
                $seenUsernames[$username] = true;

                // Skip if already exists in new DB
                $exists = User::where('school_id', $this->school->id)
                    ->where('username', $username)
                    ->exists();

                if ($exists) {
                    continue;
                }

                User::create([
                    'school_id' => $this->school->id,
                    'name' => ucfirst($username), // Old system has no display name
                    'username' => $username,
                    'password' => $this->tempPassword,
                    'role' => 'school_admin',
                    'is_active' => true,
                    'must_change_password' => true,
                    'created_at' => $admin->update_date,
                ]);

                $count++;
            }
        }

        $this->command->info("  Admin users created: {$count}");
    }

    /**
     * Step 6: Migrate students from both databases.
     * For students that appear in multiple sessions (promoted), keeps the latest entry.
     */
    private function migrateStudents(): void
    {
        $this->command->info('Migrating students...');

        $this->migrateStudentsFromDb('legacy_nursery', 'nursery', $this->nurseryLevel);
        $this->migrateStudentsFromDb('legacy_primary', 'primary', $this->primaryLevel);
    }

    private function migrateStudentsFromDb(string $connection, string $levelKey, SchoolLevel $level): void
    {
        $students = DB::connection($connection)
            ->table('students')
            ->orderBy('id')
            ->get();

        // Group by username, keep the latest (highest ID = most recently enrolled/promoted)
        $uniqueStudents = $students->groupBy('username')->map(fn ($group) => $group->last());

        $count = 0;
        $resultCount = 0;

        foreach ($uniqueStudents as $old) {
            $username = Str::lower(trim($old->username));

            // Skip if already migrated (could exist from another level's migration)
            $existingUser = User::where('school_id', $this->school->id)
                ->where('username', $username)
                ->first();

            if ($existingUser) {
                // Update to latest class if this entry is newer
                continue;
            }

            $newClassId = $this->classIdMap["{$levelKey}_{$old->classid}"] ?? null;

            if (! $newClassId) {
                Log::warning("Migration: No class mapping for {$levelKey}_{$old->classid} (student: {$old->student_name})");

                continue;
            }

            // Create user record
            $user = User::create([
                'school_id' => $this->school->id,
                'name' => $old->student_name,
                'username' => $username,
                'password' => $this->tempPassword,
                'role' => 'student',
                'level_id' => $level->id,
                'gender' => $this->mapGender($old->gender),
                'avatar_url' => $old->picture_path ? "legacy/student_images/{$old->picture_path}" : null,
                'is_active' => (bool) $old->status,
                'must_change_password' => true,
                'created_at' => $old->enrollment_date ?? now(),
            ]);

            // Create student profile
            $enrolledSessionId = isset($old->session_id) && isset($this->sessionIdMap[$old->session_id])
                ? $this->sessionIdMap[$old->session_id]
                : null;

            StudentProfile::create([
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'class_id' => $newClassId,
                'enrolled_session_id' => $enrolledSessionId,
                'enrolled_at' => $old->enrollment_date ?? now(),
            ]);

            // Migrate results (result_term1, result_term2, result_term3)
            $resultCount += $this->migrateStudentResults($user, $old, $levelKey, $newClassId);

            $count++;
        }

        $this->command->info("  {$level->name} students: {$count}, results: {$resultCount}");
    }

    /**
     * Migrate a student's term results (up to 3 per old entry).
     */
    private function migrateStudentResults(User $user, object $oldStudent, string $levelKey, int $classId): int
    {
        $count = 0;

        // Determine which session this student belongs to
        $sessionId = isset($oldStudent->session_id) && isset($this->sessionIdMap[$oldStudent->session_id])
            ? $this->sessionIdMap[$oldStudent->session_id]
            : ($this->sessionIdMap[1] ?? null); // Default to first session

        if (! $sessionId) {
            return 0;
        }

        // Get terms for this session
        $terms = Term::where('school_id', $this->school->id)
            ->where('session_id', $sessionId)
            ->orderBy('term_number')
            ->get();

        $termResults = [
            1 => $oldStudent->result_term1 ?? null,
            2 => $oldStudent->result_term2 ?? null,
            3 => $oldStudent->result_term3 ?? null,
        ];

        foreach ($termResults as $termNumber => $filePath) {
            if (empty($filePath)) {
                continue;
            }

            $term = $terms->where('term_number', $termNumber)->first();
            if (! $term) {
                continue;
            }

            // Check for duplicate
            $exists = Result::where('student_id', $user->id)
                ->where('session_id', $sessionId)
                ->where('term_id', $term->id)
                ->exists();

            if ($exists) {
                continue;
            }

            Result::create([
                'school_id' => $this->school->id,
                'student_id' => $user->id,
                'session_id' => $sessionId,
                'term_id' => $term->id,
                'class_id' => $classId,
                'file_url' => "legacy/{$filePath}", // Prefix with legacy/ — update after Cloudinary upload
                'status' => 'approved',
                'created_at' => now(),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Step 7: Migrate notices from both databases.
     */
    private function migrateNotices(): void
    {
        $this->command->info('Migrating notices...');

        $seenTitles = [];
        $count = 0;

        foreach (['legacy_nursery', 'legacy_primary'] as $connection) {
            $notices = DB::connection($connection)->table('notice')->get();

            foreach ($notices as $old) {
                // Deduplicate by title (same notices often exist in both DBs)
                $titleKey = Str::lower(trim($old->noticetitle ?? ''));
                if (isset($seenTitles[$titleKey])) {
                    continue;
                }
                $seenTitles[$titleKey] = true;

                Notice::create([
                    'school_id' => $this->school->id,
                    'title' => $old->noticetitle ?? 'Untitled Notice',
                    'content' => $old->noticedetails ?? '',
                    'image_url' => $old->picture_path ? "legacy/notice_images/{$old->picture_path}" : null,
                    'is_published' => true,
                    'published_at' => $old->postdate,
                    'created_at' => $old->postdate,
                ]);

                $count++;
            }
        }

        $this->command->info("  Notices created: {$count}");
    }

    /**
     * Step 8: Migrate assignments from both databases.
     * Old format: 1 row per class with week1-week12 columns.
     * New format: 1 row per week.
     */
    private function migrateAssignments(): void
    {
        $this->command->info('Migrating assignments...');

        $count = 0;

        // We need a session and term to link assignments to.
        // Use the first session (2024/2025) and first term as default.
        $defaultSessionId = reset($this->sessionIdMap) ?: null;
        $defaultTerm = $defaultSessionId
            ? Term::where('school_id', $this->school->id)->where('session_id', $defaultSessionId)->where('term_number', 1)->first()
            : null;

        if (! $defaultSessionId || ! $defaultTerm) {
            $this->command->warn('  No session/term found — skipping assignment migration.');

            return;
        }

        foreach (['legacy_nursery' => 'nursery', 'legacy_primary' => 'primary'] as $connection => $levelKey) {
            $assignments = DB::connection($connection)->table('assignments')->get();

            foreach ($assignments as $old) {
                $newClassId = $this->classIdMap["{$levelKey}_{$old->classid}"] ?? null;

                if (! $newClassId) {
                    continue;
                }

                for ($week = 1; $week <= 12; $week++) {
                    $weekCol = "week{$week}";
                    $filePath = $old->$weekCol ?? null;

                    if (empty($filePath)) {
                        continue;
                    }

                    // Check for duplicate
                    $exists = Assignment::where('class_id', $newClassId)
                        ->where('session_id', $defaultSessionId)
                        ->where('term_id', $defaultTerm->id)
                        ->where('week_number', $week)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    Assignment::create([
                        'school_id' => $this->school->id,
                        'class_id' => $newClassId,
                        'session_id' => $defaultSessionId,
                        'term_id' => $defaultTerm->id,
                        'week_number' => $week,
                        'title' => "Week {$week} Assignment",
                        'file_url' => "legacy/assignments/{$filePath}",
                        'status' => 'approved',
                        'created_at' => now(),
                    ]);

                    $count++;
                }
            }
        }

        $this->command->info("  Assignments created: {$count}");
    }

    /**
     * Map old gender string to new enum value.
     */
    private function mapGender(?string $gender): ?string
    {
        if (! $gender) {
            return null;
        }

        return match (Str::lower(trim($gender))) {
            'male' => 'male',
            'female' => 'female',
            default => null,
        };
    }

    /**
     * Print a summary of migrated data.
     */
    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== Migration Summary ===');
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['School', 1],
                ['School Levels', SchoolLevel::where('school_id', $this->school->id)->count()],
                ['Classes', SchoolClass::where('school_id', $this->school->id)->count()],
                ['Academic Sessions', AcademicSession::where('school_id', $this->school->id)->count()],
                ['Terms', Term::where('school_id', $this->school->id)->count()],
                ['Admin Users', User::where('school_id', $this->school->id)->where('role', 'school_admin')->count()],
                ['Students', User::where('school_id', $this->school->id)->where('role', 'student')->count()],
                ['Results', Result::where('school_id', $this->school->id)->count()],
                ['Notices', Notice::where('school_id', $this->school->id)->count()],
                ['Assignments', Assignment::where('school_id', $this->school->id)->count()],
            ]
        );
    }

    // =========================================================================
    // OPTIONAL: Cloudinary file migration (uncomment and run separately)
    // =========================================================================

    /**
     * Upload legacy files to Cloudinary and update URLs in the database.
     *
     * Run this AFTER the main migration, in a separate step:
     *   php artisan tinker --execute="(new \Database\Seeders\MigrateLegacyDataSeeder)->migrateFilesToCloudinary()"
     *
     * Prerequisites:
     * - Copy old upload folders to storage/app/legacy/ in the new project
     * - Cloudinary credentials configured in .env
     *
     * Expected folder structure in storage/app/legacy/:
     *   legacy/student_images/   (from easytouse/admin/nursery/uploads/student_images/)
     *   legacy/results/          (from easytouse/admin/nursery/uploads/results/)
     *   legacy/assignments/      (from easytouse/admin/nursery/uploads/)
     *   legacy/notice_images/    (from easytouse/admin/nursery/uploads/ notice images)
     */
    // public function migrateFilesToCloudinary(): void
    // {
    //     $uploadService = app(\App\Services\FileUploadService::class);
    //     $schoolId = School::where('slug', 'dexta-schools')->value('id');
    //
    //     // 1. Student avatars
    //     User::where('school_id', $schoolId)
    //         ->where('role', 'student')
    //         ->whereNotNull('avatar_url')
    //         ->where('avatar_url', 'like', 'legacy/%')
    //         ->chunk(50, function ($students) use ($uploadService, $schoolId) {
    //             foreach ($students as $student) {
    //                 $localPath = storage_path('app/' . $student->avatar_url);
    //                 if (! file_exists($localPath)) {
    //                     Log::warning("File not found: {$localPath}");
    //                     continue;
    //                 }
    //
    //                 try {
    //                     $result = $uploadService->uploadAvatar(
    //                         new \Illuminate\Http\UploadedFile($localPath, basename($localPath)),
    //                         $schoolId
    //                     );
    //                     $student->update(['avatar_url' => $result['url']]);
    //                 } catch (\Exception $e) {
    //                     Log::error("Cloudinary upload failed for avatar {$student->id}: {$e->getMessage()}");
    //                 }
    //             }
    //         });
    //
    //     // 2. Result PDFs
    //     Result::where('school_id', $schoolId)
    //         ->where('file_url', 'like', 'legacy/%')
    //         ->chunk(50, function ($results) use ($uploadService, $schoolId) {
    //             foreach ($results as $result) {
    //                 $localPath = storage_path('app/' . $result->file_url);
    //                 if (! file_exists($localPath)) {
    //                     Log::warning("File not found: {$localPath}");
    //                     continue;
    //                 }
    //
    //                 try {
    //                     $uploaded = $uploadService->uploadResult(
    //                         new \Illuminate\Http\UploadedFile($localPath, basename($localPath)),
    //                         $schoolId
    //                     );
    //                     $result->update([
    //                         'file_url' => $uploaded['url'],
    //                         'file_public_id' => $uploaded['public_id'],
    //                     ]);
    //                 } catch (\Exception $e) {
    //                     Log::error("Cloudinary upload failed for result {$result->id}: {$e->getMessage()}");
    //                 }
    //             }
    //         });
    //
    //     echo "File migration to Cloudinary complete.\n";
    // }
}
