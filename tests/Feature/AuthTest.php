<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_skips_verification_if_no_gateways_configured()
    {
        // phpunit.xml sets MAIL_HOST=127.0.0.1 and empty SMS key → auto-verify
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent_data_processing' => 'on',
        ]);

        $response->assertRedirect(route('student.dashboard'));

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->studentProfile);
    }

    public function test_registration_verification_depends_on_runtime_env_mail_host()
    {
        // AuthController reads env('MAIL_HOST') directly. Once PHPUnit boots,
        // mid-test putenv() does not reliably change env(), so SMTP-forced
        // verification cannot be asserted without refactoring to config().
        $this->markTestSkipped(
            'Gap: AuthController uses env(MAIL_HOST)/env(SMS_PROVIDER_KEY); values are fixed at bootstrap and cannot be toggled per test via putenv.'
        );
    }
}
