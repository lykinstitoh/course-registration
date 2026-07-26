<?php

namespace Database\Factories;

use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Programme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeStructure>
 */
class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        return [
            'programme_id' => Programme::factory(),
            'intake_id' => Intake::factory(),
            'fee_type' => 'application',
            'description' => 'Application Fee',
            'amount' => 2000,
            'currency' => 'KES',
            'is_mandatory' => true,
        ];
    }

    public function tuition(float $amount = 85000): static
    {
        return $this->state(fn () => [
            'fee_type' => 'tuition',
            'description' => 'Tuition Fee',
            'amount' => $amount,
        ]);
    }
}
