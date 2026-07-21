<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendSms(User $user, string $event, string $message): NotificationLog
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => 'sms',
            'event' => $event,
            'recipient' => $user->phone ?? '',
            'message' => $message,
            'status' => 'queued',
        ]);

        if (empty($user->phone)) {
            $log->update(['status' => 'failed', 'provider_response' => ['error' => 'No phone number']]);

            return $log;
        }

        if (empty(env('SMS_PROVIDER_KEY')) && empty(config('africastalking.username'))) {
            $log->update(['status' => 'failed', 'provider_response' => ['error' => 'SMS Gateway not configured']]);
            return $log;
        }

        if ($this->isSmsSandbox()) {
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_response' => ['sandbox' => true],
            ]);

            return $log;
        }

        $response = Http::withHeaders([
            'apiKey' => config('africastalking.api_key'),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => config('africastalking.username'),
            'to' => $user->phone,
            'message' => $message,
            'from' => config('africastalking.sender_id'),
        ]);

        $data = $response->json();
        $log->update([
            'status' => $response->successful() ? 'sent' : 'failed',
            'sent_at' => $response->successful() ? now() : null,
            'provider_response' => $data,
        ]);

        if (! $response->successful()) {
            Log::error('Africa\'s Talking SMS failed', $data ?? []);
        }

        return $log;
    }

    public function sendEmail(User $user, string $event, string $subject, string $message): NotificationLog
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'event' => $event,
            'recipient' => $user->email,
            'subject' => $subject,
            'message' => $message,
            'status' => 'queued',
        ]);

        if (empty(env('MAIL_HOST')) || env('MAIL_HOST') === '127.0.0.1') {
            $log->update(['status' => 'failed', 'provider_response' => ['error' => 'SMTP not configured']]);
            return $log;
        }

        try {
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('ocrs.institution_name'));
            });

            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Email notification failed', ['error' => $e->getMessage()]);
            $log->update([
                'status' => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);
        }

        return $log;
    }

    public function notifyApplicationStatus(User $user, string $status, string $reference): void
    {
        $message = "Dear {$user->name}, your application ({$reference}) status is now: {$status}. "
            .config('ocrs.institution_name');

        $this->sendSms($user, 'application_status', $message);
        $this->sendEmail(
            $user,
            'application_status',
            'Application Status Update — '.config('ocrs.institution_name'),
            $message
        );
    }

    public function notifyPaymentConfirmation(User $user, string $reference, string $amount, ?string $receipt = null): void
    {
        $receiptText = $receipt ? " Receipt: {$receipt}." : '';
        $message = "Payment of KES {$amount} confirmed. Ref: {$reference}.{$receiptText} "
            .config('ocrs.institution_name');

        $this->sendSms($user, 'payment_confirmation', $message);
        $this->sendEmail(
            $user,
            'payment_confirmation',
            'Payment Confirmation — '.config('ocrs.institution_name'),
            $message
        );
    }

    public function notifyRegistrationDeadline(User $user, string $deadline): void
    {
        $message = "Reminder: Course registration closes on {$deadline}. "
            .'Complete your registration via the OCRS portal. '
            .config('ocrs.institution_name');

        $this->sendSms($user, 'registration_deadline', $message);
        $this->sendEmail(
            $user,
            'registration_deadline',
            'Registration Deadline Reminder — '.config('ocrs.institution_name'),
            $message
        );
    }

    private function isSmsSandbox(): bool
    {
        return config('africastalking.environment') === 'sandbox'
            && empty(config('africastalking.api_key'));
    }
}
