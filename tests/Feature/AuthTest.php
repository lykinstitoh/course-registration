<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_skips_verification_if_no_gateways_configured()
    {
        // Mock ENV to act as if no mail/sms is configured
        putenv('MAIL_HOST=127.0.0.1');
        putenv('SMS_PROVIDER_KEY=');
        
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
    }

    public function test_registration_requires_verification_if_smtp_configured()
    {
        putenv('MAIL_HOST=smtp.mailtrap.io');
        
        $response = $this->post(route('register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0700000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent_data_processing' => 'on',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }
}
