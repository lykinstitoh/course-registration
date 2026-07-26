<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\CourseUnit;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Programme;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\SystemSetting;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private StudentProfile $profile;

    private User $admin;

    private User $finance;

    private Programme $programme;

    private Intake $intake;

    private Campus $campus;

    private Semester $semester;

    private CourseUnit $unitA;

    private CourseUnit $unitB;

    private FeeStructure $applicationFee;

    private FeeStructure $tuitionFee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->seedPaymentSettings();

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@test.ocrs',
        ]);
        $this->finance = User::factory()->create([
            'email' => 'finance@test.ocrs',
            'role' => UserRole::Finance,
        ]);

        $this->student = User::factory()->student()->create([
            'email' => 'student@test.ocrs',
        ]);
        $this->profile = StudentProfile::factory()->create([
            'user_id' => $this->student->id,
            'kcse_mean_grade' => 8.00,
        ]);

        $this->programme = Programme::factory()->create([
            'code' => 'BSC-CS',
            'name' => 'BSc Computer Science',
            'minimum_kcse_grade' => 7.00,
        ]);
        $this->intake = Intake::factory()->create();
        $this->campus = Campus::factory()->create();
        $this->semester = Semester::factory()->create([
            'intake_id' => $this->intake->id,
        ]);

        $this->unitA = CourseUnit::factory()->create([
            'code' => 'CS101',
            'semester_level' => 1,
        ]);
        $this->unitB = CourseUnit::factory()->create([
            'code' => 'MATH101',
            'semester_level' => 1,
        ]);
        $this->programme->courseUnits()->attach([
            $this->unitA->id => ['is_core' => true],
            $this->unitB->id => ['is_core' => true],
        ]);

        $this->applicationFee = FeeStructure::factory()->create([
            'programme_id' => $this->programme->id,
            'intake_id' => $this->intake->id,
            'fee_type' => 'application',
            'amount' => 2000,
            'is_mandatory' => true,
        ]);
        $this->tuitionFee = FeeStructure::factory()->tuition()->create([
            'programme_id' => $this->programme->id,
            'intake_id' => $this->intake->id,
        ]);

        TimetableEntry::create([
            'course_unit_id' => $this->unitA->id,
            'semester_id' => $this->semester->id,
            'day_of_week' => 'Monday',
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'venue' => 'Lab 1',
            'lecturer' => 'Dr. Test',
        ]);

        $this->assertGreaterThanOrEqual(3, DocumentRequirement::where('is_required', true)->count());
    }

    public function test_full_student_journey_register_apply_pay_enroll_results(): void
    {
        // 1. Submit application → PendingFee
        $submit = $this->actingAs($this->student)->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->programme->id,
            'intake_id' => $this->intake->id,
            'campus_id' => $this->campus->id,
            'kcse_mean_grade' => 8.0,
            'kcse_index_number' => '12345678001',
            'kcse_year' => now()->year - 1,
            'national_id' => '12345678',
            'county' => 'Nairobi',
            'date_of_birth' => '2002-01-01',
            'gender' => 'Male',
        ]);
        $submit->assertRedirect(route('student.payments.index'));

        $application = $this->profile->applications()->latest()->first();
        $this->assertNotNull($application);
        $this->assertSame(ApplicationStatus::PendingFee, $application->status);

        // 2. Pay application fee (bank) → admin approve → Submitted
        $appPay = $this->actingAs($this->student)->post(route('student.payments.initiate'), [
            'fee_structure_id' => $this->applicationFee->id,
            'method' => 'bank_transfer',
            'bank_reference' => 'BANK-APP-001',
            'receipt' => UploadedFile::fake()->create('app-receipt.pdf', 100, 'application/pdf'),
        ]);
        $appPay->assertRedirect();

        $applicationPayment = $this->profile->payments()->latest()->first();
        $this->assertSame(PaymentStatus::Pending, $applicationPayment->status);

        $this->actingAs($this->finance)
            ->post(route('admin.payments.review', $applicationPayment), ['action' => 'approve'])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $applicationPayment->refresh();
        $this->assertSame(PaymentStatus::Completed, $applicationPayment->status);

        // 3. Upload + verify required documents
        foreach (['kcse_certificate', 'national_id', 'passport_photo'] as $code) {
            $this->actingAs($this->student)->post(route('student.documents.store'), [
                'document_type' => $code,
                'file' => UploadedFile::fake()->create("{$code}.pdf", 100, 'application/pdf'),
            ])->assertRedirect();
        }

        $docs = Document::where('student_profile_id', $this->profile->id)->get();
        $this->assertCount(3, $docs);

        foreach ($docs as $doc) {
            $this->actingAs($this->admin)
                ->post(route('admin.documents.review', $doc), ['action' => 'verify'])
                ->assertRedirect();
            $doc->refresh();
            $this->assertSame(DocumentStatus::Verified, $doc->status);
        }

        $this->assertTrue($this->profile->fresh()->hasAllRequiredDocumentsVerified());
        $this->assertFalse($this->profile->fresh()->isEnrolled()); // tuition still unpaid

        // 4. Admin approve application → admission letter
        $this->actingAs($this->admin)
            ->post(route('admin.applications.review', $application), [
                'action' => 'approve',
                'notes' => 'Eligible',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Approved, $application->status);
        $this->assertDatabaseHas('admission_letters', [
            'application_id' => $application->id,
        ]);
        $this->assertNotNull($this->profile->fresh()->admission_number);

        // 5. Pay tuition → enrolled
        $tuitionInit = $this->actingAs($this->student)->post(route('student.payments.initiate'), [
            'fee_structure_id' => $this->tuitionFee->id,
            'method' => 'bank_transfer',
            'bank_reference' => 'BANK-TUI-'.uniqid(),
            'receipt' => UploadedFile::fake()->create('tuition-receipt.pdf', 100, 'application/pdf'),
        ]);
        $tuitionInit->assertSessionHasNoErrors();
        $tuitionInit->assertRedirect();

        $tuitionPayment = $this->profile->payments()
            ->where('fee_structure_id', $this->tuitionFee->id)
            ->latest()
            ->first();
        $this->assertNotNull($tuitionPayment);
        $this->assertSame(PaymentStatus::Pending, $tuitionPayment->status);

        $this->actingAs($this->finance)
            ->post(route('admin.payments.review', $tuitionPayment), ['action' => 'approve'])
            ->assertRedirect();

        $tuitionPayment->refresh();
        $this->assertSame(PaymentStatus::Completed, $tuitionPayment->status);
        $this->assertGreaterThan(0, $this->profile->fresh()->getPaidTuitionAmount());
        $this->assertTrue($this->profile->fresh()->isEnrolled());

        // 6. Register units → Confirmed + pending results
        $this->actingAs($this->student)->post(route('student.registrations.store'), [
            'semester_id' => $this->semester->id,
            'course_unit_ids' => [$this->unitA->id, $this->unitB->id],
        ])->assertRedirect(route('student.registrations.index'));

        $registration = Registration::where('student_profile_id', $this->profile->id)->first();
        $this->assertNotNull($registration);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);
        $this->assertCount(2, $registration->items);
        $this->assertSame(2, Result::where('student_profile_id', $this->profile->id)->count());

        // 7. Timetable accessible
        $this->actingAs($this->student)
            ->get(route('student.timetable'))
            ->assertOk()
            ->assertSee('Lab 1');

        // 8. Admin enter + publish results → student can view
        $results = Result::where('student_profile_id', $this->profile->id)->get();
        $marksPayload = [];
        foreach ($results as $result) {
            $marksPayload[$result->id] = 75;
        }

        $this->actingAs($this->admin)->post(route('admin.results.bulk'), [
            'semester_id' => $this->semester->id,
            'marks' => $marksPayload,
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('admin.results.publish'), [
            'semester_id' => $this->semester->id,
        ])->assertRedirect();

        foreach ($results as $result) {
            $result->refresh();
            $this->assertSame(ResultStatus::Published, $result->status);
            $this->assertSame('A', $result->grade);
        }

        $this->actingAs($this->student)
            ->get(route('student.results'))
            ->assertOk()
            ->assertSee('CS101')
            ->assertSee('A');
    }

    public function test_enrollment_blocked_without_verified_docs_even_if_tuition_paid(): void
    {
        $application = $this->profile->applications()->create([
            'reference' => 'APP-TESTBLOCK',
            'programme_id' => $this->programme->id,
            'intake_id' => $this->intake->id,
            'status' => ApplicationStatus::Approved,
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);
        $this->profile->update(['admission_number' => 'OCRS-00099']);

        $this->profile->payments()->create([
            'reference' => 'PAY-TUI-DONE',
            'fee_structure_id' => $this->tuitionFee->id,
            'amount' => $this->tuitionFee->amount,
            'method' => 'bank_transfer',
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);

        $this->assertFalse($this->profile->fresh()->isEnrolled());

        $this->actingAs($this->student)
            ->post(route('student.registrations.store'), [
                'semester_id' => $this->semester->id,
                'course_unit_ids' => [$this->unitA->id],
            ])
            ->assertRedirect(route('student.enrollment.index'));
    }

    public function test_timetable_redirects_without_confirmed_registration(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.timetable'))
            ->assertRedirect(route('student.registrations.index'));
    }

    public function test_programme_without_units_yields_empty_registration_catalog(): void
    {
        $bba = Programme::factory()->create([
            'code' => 'BBA',
            'minimum_kcse_grade' => 6.00,
        ]);

        $this->profile->applications()->create([
            'reference' => 'APP-BBA-GAP',
            'programme_id' => $bba->id,
            'intake_id' => $this->intake->id,
            'status' => ApplicationStatus::Approved,
            'submitted_at' => now(),
        ]);

        // Satisfy enrollment gates
        foreach (DocumentRequirement::where('is_required', true)->whereNotIn('code', ['national_id', 'birth_certificate'])->pluck('code') as $code) {
            Document::create([
                'student_profile_id' => $this->profile->id,
                'application_id' => $this->profile->applications()->latest('id')->value('id'),
                'document_type' => $code,
                'original_filename' => "{$code}.pdf",
                'stored_path' => "documents/{$code}.pdf",
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'status' => DocumentStatus::Verified,
                'verified_at' => now(),
            ]);
        }

        // Adult profile → National ID is the required identity document
        Document::create([
            'student_profile_id' => $this->profile->id,
            'application_id' => $this->profile->applications()->latest('id')->value('id'),
            'document_type' => 'national_id',
            'original_filename' => 'national_id.pdf',
            'stored_path' => 'documents/national_id.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'status' => DocumentStatus::Verified,
            'verified_at' => now(),
        ]);

        FeeStructure::factory()->tuition()->create([
            'programme_id' => $bba->id,
            'intake_id' => $this->intake->id,
        ]);
        $tuition = FeeStructure::where('programme_id', $bba->id)->where('fee_type', 'tuition')->first();
        $this->profile->payments()->create([
            'reference' => 'PAY-BBA-TUI',
            'fee_structure_id' => $tuition->id,
            'amount' => $tuition->amount,
            'method' => 'bank_transfer',
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);

        $this->assertTrue($this->profile->fresh()->isEnrolled());

        $response = $this->actingAs($this->student)->get(route('student.registrations.index'));
        $response->assertOk();
        $this->assertTrue($bba->courseUnits()->count() === 0);
    }

    public function test_kcse_below_minimum_rejects_application(): void
    {
        $this->actingAs($this->student)->post(route('student.applications.store'), [
            'action' => 'submit',
            'programme_id' => $this->programme->id,
            'intake_id' => $this->intake->id,
            'kcse_mean_grade' => 5.0,
            'kcse_index_number' => '12345678001',
            'kcse_year' => now()->year - 1,
            'national_id' => '87654321',
            'county' => 'Nairobi',
            'date_of_birth' => '2003-05-01',
            'gender' => 'Female',
        ])->assertRedirect(route('student.applications.index'));

        $application = $this->profile->applications()->latest()->first();
        $this->assertSame(ApplicationStatus::Rejected, $application->status);
    }

    private function seedPaymentSettings(): void
    {
        foreach ([
            ['group' => 'payment', 'key' => 'enable_mpesa', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'enable_bank_transfer', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'fees', 'key' => 'min_tuition_percentage', 'value' => '50', 'type' => 'integer'],
            ['group' => 'admission', 'key' => 'allow_late_applications', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'bank_name', 'value' => 'Equity Bank', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'bank_account_name', 'value' => 'OCRS', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'bank_account_number', 'value' => '0123456789', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'bank_branch', 'value' => 'Nairobi', 'type' => 'string'],
        ] as $setting) {
            SystemSetting::create($setting);
        }
    }
}
