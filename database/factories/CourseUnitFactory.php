<?php

namespace Database\Factories;

use App\Models\CourseUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseUnit>
 */
class CourseUnitFactory extends Factory
{
    protected $model = CourseUnit::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CU###')),
            'name' => fake()->words(3, true),
            'credit_units' => 3,
            'capacity' => 60,
            'semester_level' => 1,
            'is_active' => true,
        ];
    }
}
