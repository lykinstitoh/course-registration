<?php

namespace Database\Factories;

use App\Models\Intake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intake>
 */
class IntakeFactory extends Factory
{
    protected $model = Intake::class;

    public function definition(): array
    {
        return [
            'name' => 'Test Intake '.fake()->unique()->year(),
            'academic_year' => now()->year.'/'.(now()->year + 1),
            'application_opens' => now()->subMonths(2)->toDateString(),
            'application_closes' => now()->addMonths(2)->toDateString(),
            'registration_opens' => now()->toDateString(),
            'registration_closes' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ];
    }
}
