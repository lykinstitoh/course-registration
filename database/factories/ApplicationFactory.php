<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Intake;
use App\Models\Programme;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'reference' => 'APP-'.strtoupper(Str::random(8)),
            'student_profile_id' => StudentProfile::factory(),
            'programme_id' => Programme::factory(),
            'intake_id' => Intake::factory(),
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ];
    }
}
