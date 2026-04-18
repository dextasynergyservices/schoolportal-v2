<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $platform = School::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'platform'],
            [
                'name' => 'SchoolPortal Platform',
                'email' => 'platform@schoolportal.test',
                'country' => 'Nigeria',
                'is_active' => true,
            ],
        );

        User::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $platform->id, 'username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@schoolportal.test',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info('Super admin seeded: username=superadmin, password=password');
    }
}
