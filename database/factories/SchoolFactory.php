<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'email' => fake()->unique()->companyEmail(),
            'country' => 'Nigeria',
            'is_active' => true,
            'ai_free_credits' => 15,
            'ai_purchased_credits' => 0,
            'ai_credits_total_purchased' => 0,
        ];
    }
}
