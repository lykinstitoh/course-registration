<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\Intake;
use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_save_application_as_draft()
    {
        $user = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create(['user_id' => $user->id]);
        $programme = Programme::factory()->create(['is_active' => true]);
        $intake = Intake::factory()->create(['is_active' => true]);
        $campus = Campus::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('student.applications.store'), [
            'action' => 'draft',
            'programme_id' => $programme->id,
            'intake_id' => $intake->id,
            'campus_id' => $campus->id,
            'kcse_mean_grade' => 7.0, // C+
            'kcse_index_number' => '1234567890',
            'kcse_year' => 2023,
            'national_id' => '12345678',
            'county' => 'Nairobi',
            'date_of_birth' => '2000-01-01',
            'gender' => 'Male',
        ]);

        $response->assertRedirect(route('student.applications.index'));
        $response->assertSessionHas('success', 'Application saved as draft.');

        $this->assertDatabaseHas('applications', [
            'student_profile_id' => $profile->id,
            'status' => ApplicationStatus::Draft,
            'campus_id' => $campus->id,
        ]);
    }

    public function test_admin_can_review_and_approve_application()
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $application = \App\Models\Application::factory()->create(['status' => ApplicationStatus::Submitted]);
        
        $response = $this->actingAs($admin)->post(route('admin.applications.review', $application), [
            'action' => 'approve',
            'notes' => 'All good',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Approved,
        ]);

        $this->assertDatabaseHas('application_status_history', [
            'application_id' => $application->id,
            'status' => ApplicationStatus::Approved,
            'notes' => 'All good',
        ]);

        $this->assertDatabaseHas('admission_letters', [
            'application_id' => $application->id,
        ]);
    }
}
