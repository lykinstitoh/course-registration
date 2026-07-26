<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private StudentProfile $profile;

    private Programme $degree;

    private Programme $diploma;

    private Intake $intake;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::create([
            'group' => 'admission',
            'key' => 'allow_late_applications',
            'value' => '0',
            'type' => 'boolean',
        ]);

        $this->student = User::factory()->student()->create();
        $this->profile = StudentProfile::factory()->create(['user_id' => $this->student->id]);
        $this->degree = Programme::factory()->create([
            'award_level' => 'degree',
            'minimum_kcse_grade' => 7.00,
        ]);
        $this->diploma = Programme::factory()->create([
            'award_level' => 'diploma',
            'minimum_kcse_grade' => 5.00,
        ]);
        $this->intake = Intake::factory()->create();

        FeeStructure::factory()->create([
            'programme_id' => $this->degree->id,
            'intake_id' => $this->intake->id,
            'fee_type' => 'application',
            'is_mandatory' => true,
        ]);
        FeeStructure::factory()->create([
            'programme_id' => $this->diploma->id,
            'intake_id' => $this->intake->id,
            'fee_type' => 'application',
            'is_mandatory' => true,
        ]);
    }

    public function test_rejects_applicant_younger_than_degree_minimum_age(): void
    {
        // 16 years old — allowed for diploma floor, too young for degree (17+)
        $dob = now()->subYears(16)->subMonth()->toDateString();

        $response = $this->actingAs($this->student)->from(route('student.applications.create'))->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->degree->id,
            'intake_id' => $this->intake->id,
            'kcse_mean_grade' => 8,
            'kcse_index_number' => '12345678001',
            'kcse_year' => now()->year - 1,
            'national_id' => '12345678',
            'county' => 'Nairobi',
            'date_of_birth' => $dob,
            'gender' => 'Female',
            'next_of_kin_name' => 'Jane Parent',
            'next_of_kin_phone' => '0712345678',
        ]);

        $response->assertSessionHasErrors('date_of_birth');
    }

    public function test_requires_guardian_details_for_under_18(): void
    {
        $dob = now()->subYears(17)->subMonths(2)->toDateString();

        $response = $this->actingAs($this->student)->from(route('student.applications.create'))->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->degree->id,
            'intake_id' => $this->intake->id,
            'kcse_mean_grade' => 8,
            'kcse_index_number' => '12345678001',
            'kcse_year' => now()->year - 1,
            'national_id' => '12345678',
            'county' => 'Nairobi',
            'date_of_birth' => $dob,
            'gender' => 'Male',
        ]);

        $response->assertSessionHasErrors(['next_of_kin_name', 'next_of_kin_phone']);
    }

    public function test_rejects_invalid_kcse_index_and_identity_formats(): void
    {
        $response = $this->actingAs($this->student)->from(route('student.applications.create'))->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->diploma->id,
            'intake_id' => $this->intake->id,
            'kcse_mean_grade' => 6,
            'kcse_index_number' => '12345',
            'kcse_year' => now()->year - 1,
            'national_id' => 'ID#bad',
            'county' => 'Nairobi',
            'date_of_birth' => '2005-06-01',
            'gender' => 'Male',
            'next_of_kin_phone' => '12345',
        ]);

        $response->assertSessionHasErrors(['kcse_index_number', 'national_id', 'next_of_kin_phone']);
    }

    public function test_rejects_kcse_year_inconsistent_with_date_of_birth(): void
    {
        // Born 2008, claiming KCSE in 2015 → age ~7 at exam
        $response = $this->actingAs($this->student)->from(route('student.applications.create'))->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->diploma->id,
            'intake_id' => $this->intake->id,
            'kcse_mean_grade' => 6,
            'kcse_index_number' => '12345678001',
            'kcse_year' => 2015,
            'national_id' => '12345678',
            'county' => 'Nairobi',
            'date_of_birth' => '2008-01-01',
            'gender' => 'Female',
            'next_of_kin_name' => 'Guardian Name',
            'next_of_kin_phone' => '0712345678',
        ]);

        $response->assertSessionHasErrors('kcse_year');
    }

    public function test_accepts_valid_adult_degree_application_payload(): void
    {
        $response = $this->actingAs($this->student)->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->degree->id,
            'intake_id' => $this->intake->id,
            'kcse_mean_grade' => 8,
            'kcse_index_number' => '12345678001',
            'kcse_year' => now()->year - 1,
            'national_id' => '12345678',
            'county' => 'Nairobi',
            'date_of_birth' => '2004-03-15',
            'gender' => 'Male',
            'next_of_kin_name' => 'Parent Name',
            'next_of_kin_phone' => '254712345678',
        ]);

        $response->assertRedirect(route('student.payments.index'));
        $this->assertDatabaseHas('applications', [
            'student_profile_id' => $this->profile->id,
            'status' => ApplicationStatus::PendingFee->value,
        ]);
    }
}
