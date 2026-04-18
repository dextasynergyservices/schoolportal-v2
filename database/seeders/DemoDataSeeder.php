<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\ParentProfile;
use App\Models\ParentStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\StudentProfile;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create the demo school ──
        $school = School::withoutGlobalScopes()->create([
            'name' => 'Dexta Schools',
            'slug' => 'dexta-schools',
            'custom_domain' => 'localhost',
            'email' => 'admin@dextaschools.com',
            'phone' => '08012345678',
            'address' => '123 Education Lane, Lagos',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'motto' => 'Excellence in Education',
            'is_active' => true,
            'settings' => [
                'branding' => [
                    'primary_color' => '#4F46E5',
                    'secondary_color' => '#F59E0B',
                    'accent_color' => '#10B981',
                ],
                'portal' => [
                    'show_public_homepage' => true,
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

        // Bind school for tenant scoping in this seeder
        app()->instance('current.school', $school);

        // ── 2. Create school levels ──
        $nursery = SchoolLevel::create([
            'school_id' => $school->id,
            'name' => 'Nursery',
            'slug' => 'nursery',
            'sort_order' => 1,
        ]);

        $primary = SchoolLevel::create([
            'school_id' => $school->id,
            'name' => 'Primary',
            'slug' => 'primary',
            'sort_order' => 2,
        ]);

        // ── 3. Create school admin ──
        $admin = User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => 'Admin User',
            'email' => 'admin@dextaschools.com',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'school_admin',
            'is_active' => true,
        ]);

        // ── 4. Create teachers ──
        $teacherNursery = User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => 'Mrs. Amina Bello',
            'username' => 'amina.bello',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'level_id' => $nursery->id,
            'gender' => 'female',
            'is_active' => true,
        ]);

        $teacherPrimary = User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => 'Mr. Bola Okafor',
            'username' => 'bola.okafor',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'level_id' => $primary->id,
            'gender' => 'male',
            'is_active' => true,
        ]);

        // ── 5. Create classes ──
        $nursery1 = SchoolClass::create([
            'school_id' => $school->id,
            'level_id' => $nursery->id,
            'name' => 'Nursery 1',
            'slug' => 'nursery-1',
            'sort_order' => 1,
            'capacity' => 30,
            'teacher_id' => $teacherNursery->id,
        ]);

        $nursery2 = SchoolClass::create([
            'school_id' => $school->id,
            'level_id' => $nursery->id,
            'name' => 'Nursery 2',
            'slug' => 'nursery-2',
            'sort_order' => 2,
            'capacity' => 30,
        ]);

        $primary1 = SchoolClass::create([
            'school_id' => $school->id,
            'level_id' => $primary->id,
            'name' => 'Primary 1',
            'slug' => 'primary-1',
            'sort_order' => 1,
            'capacity' => 35,
            'teacher_id' => $teacherPrimary->id,
        ]);

        $primary2 = SchoolClass::create([
            'school_id' => $school->id,
            'level_id' => $primary->id,
            'name' => 'Primary 2',
            'slug' => 'primary-2',
            'sort_order' => 2,
            'capacity' => 35,
        ]);

        $primary3 = SchoolClass::create([
            'school_id' => $school->id,
            'level_id' => $primary->id,
            'name' => 'Primary 3',
            'slug' => 'primary-3',
            'sort_order' => 3,
            'capacity' => 35,
        ]);

        // ── 6. Create academic session and terms ──
        $session = AcademicSession::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
            'status' => 'active',
        ]);

        Term::create([
            'school_id' => $school->id,
            'session_id' => $session->id,
            'term_number' => 1,
            'name' => 'First Term',
            'start_date' => '2025-09-01',
            'end_date' => '2025-12-15',
            'is_current' => true,
            'status' => 'active',
        ]);

        Term::create([
            'school_id' => $school->id,
            'session_id' => $session->id,
            'term_number' => 2,
            'name' => 'Second Term',
            'start_date' => '2026-01-10',
            'end_date' => '2026-04-15',
            'status' => 'upcoming',
        ]);

        Term::create([
            'school_id' => $school->id,
            'session_id' => $session->id,
            'term_number' => 3,
            'name' => 'Third Term',
            'start_date' => '2026-05-01',
            'end_date' => '2026-07-31',
            'status' => 'upcoming',
        ]);

        // ── 7. Create students ──
        $students = [
            ['name' => 'Emeka Adamu', 'username' => 'emeka.adamu', 'gender' => 'male', 'class' => $primary3, 'level' => $primary],
            ['name' => 'Amina Adamu', 'username' => 'amina.adamu', 'gender' => 'female', 'class' => $nursery2, 'level' => $nursery],
            ['name' => 'Chidi Nwosu', 'username' => 'chidi.nwosu', 'gender' => 'male', 'class' => $primary1, 'level' => $primary],
            ['name' => 'Fatima Ahmed', 'username' => 'fatima.ahmed', 'gender' => 'female', 'class' => $primary1, 'level' => $primary],
            ['name' => 'Bola Johnson', 'username' => 'bola.johnson', 'gender' => 'male', 'class' => $nursery1, 'level' => $nursery],
            ['name' => 'Grace Obi', 'username' => 'grace.obi', 'gender' => 'female', 'class' => $primary2, 'level' => $primary],
        ];

        $studentUsers = [];
        foreach ($students as $s) {
            $user = User::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'name' => $s['name'],
                'username' => $s['username'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'level_id' => $s['level']->id,
                'gender' => $s['gender'],
                'is_active' => true,
            ]);

            StudentProfile::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'class_id' => $s['class']->id,
                'enrolled_session_id' => $session->id,
            ]);

            $studentUsers[$s['username']] = $user;
        }

        // ── 8. Create a parent linked to Emeka and Amina ──
        $parentUser = User::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'name' => 'Mrs. Adamu',
            'username' => 'parent.adamu',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'phone' => '08098765432',
            'gender' => 'female',
            'is_active' => true,
        ]);

        ParentProfile::withoutGlobalScopes()->create([
            'user_id' => $parentUser->id,
            'school_id' => $school->id,
            'occupation' => 'Business Owner',
            'relationship' => 'mother',
        ]);

        // Link parent to children
        ParentStudent::withoutGlobalScopes()->create([
            'parent_id' => $parentUser->id,
            'student_id' => $studentUsers['emeka.adamu']->id,
            'school_id' => $school->id,
        ]);

        ParentStudent::withoutGlobalScopes()->create([
            'parent_id' => $parentUser->id,
            'student_id' => $studentUsers['amina.adamu']->id,
            'school_id' => $school->id,
        ]);

        // ── Summary ──
        $this->command->info('Demo data seeded successfully!');
        $this->command->info('School: Dexta Schools (ID: '.$school->id.')');
        $this->command->newLine();
        $this->command->info('Login credentials (password: "password" for all):');
        $this->command->table(
            ['Role', 'Username', 'Name'],
            [
                ['school_admin', 'admin', 'Admin User'],
                ['teacher', 'amina.bello', 'Mrs. Amina Bello'],
                ['teacher', 'bola.okafor', 'Mr. Bola Okafor'],
                ['student', 'emeka.adamu', 'Emeka Adamu'],
                ['student', 'amina.adamu', 'Amina Adamu'],
                ['parent', 'parent.adamu', 'Mrs. Adamu'],
            ]
        );
    }
}
