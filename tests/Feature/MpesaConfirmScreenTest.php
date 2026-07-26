<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaConfirmScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_mpesa_confirmation_screen(): void
    {
        $student = User::factory()->student()->create(['phone' => '0712345678']);
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $programme = Programme::factory()->create();
        $intake = Intake::factory()->create();
        $fee = FeeStructure::factory()->create([
            'programme_id' => $programme->id,
            'intake_id' => $intake->id,
            'fee_type' => 'application',
            'amount' => 2000,
        ]);

        $payment = Payment::create([
            'reference' => 'PAY-CONFIRM1',
            'student_profile_id' => $profile->id,
            'fee_structure_id' => $fee->id,
            'amount' => 2000,
            'method' => 'mpesa',
            'status' => PaymentStatus::Processing,
            'mpesa_checkout_request_id' => 'ws_CO_123',
            'gateway_response' => ['customer_phone' => '0712345678'],
        ]);

        $this->actingAs($student)
            ->get(route('student.payments.confirm', $payment))
            ->assertOk()
            ->assertSee('Confirm on your phone')
            ->assertSee('0712345678')
            ->assertSee('PAY-CONFIRM1')
            ->assertSee('KES 2,000');
    }

    public function test_student_cannot_view_another_students_confirmation_screen(): void
    {
        $owner = User::factory()->student()->create();
        $ownerProfile = StudentProfile::factory()->create(['user_id' => $owner->id]);
        $intruder = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $intruder->id]);

        $fee = FeeStructure::factory()->create();
        $payment = Payment::create([
            'reference' => 'PAY-PRIVATE1',
            'student_profile_id' => $ownerProfile->id,
            'fee_structure_id' => $fee->id,
            'amount' => 2000,
            'method' => 'mpesa',
            'status' => PaymentStatus::Processing,
        ]);

        $this->actingAs($intruder)
            ->get(route('student.payments.confirm', $payment))
            ->assertForbidden();
    }
}
