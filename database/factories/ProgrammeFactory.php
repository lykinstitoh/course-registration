<?php

namespace Database\Factories;

use App\Models\Programme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Programme>
 */
class ProgrammeFactory extends Factory
{
    protected $model = Programme::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PRG-###')),
            'name' => fake()->words(3, true),
            'department' => 'Computing',
            'award_level' => 'degree',
            'duration_semesters' => 8,
            'minimum_kcse_grade' => 7.00,
            'cue_accreditation_ref' => 'CUE/TEST/001',
            'is_active' => true,
        ];
    }
}
