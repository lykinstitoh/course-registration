<?php

namespace Database\Factories;

use App\Models\Intake;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'name' => 'Semester 1',
            'sequence' => 1,
            'registration_deadline' => now()->addMonths(2)->toDateString(),
            'starts_on' => now()->addMonth()->toDateString(),
            'ends_on' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ];
    }
}
