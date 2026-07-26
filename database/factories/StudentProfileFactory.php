<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'national_id' => fake()->numerify('########'),
            'kcse_mean_grade' => 8.00,
            'kcse_index_number' => fake()->numerify('###########'),
            'kcse_year' => now()->year - 1,
            'date_of_birth' => now()->subYears(20)->toDateString(),
            'gender' => 'Male',
            'county' => 'Nairobi',
            'consent_data_processing' => true,
            'consent_given_at' => now(),
        ];
    }
}
