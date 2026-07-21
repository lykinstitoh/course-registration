<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use App\Services\Notifications\NotificationService;

class MpesaService
{
    public function __construct(private NotificationService $notifications) {}

    public function initiateStkPush(Payment $payment, string $phoneNumber): array
    {
        try {
            $this->ensureStkConfiguration();
            $phone = $this->formatPhone($phoneNumber);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return $this->failPayment($payment, $exception->getMessage());
        }
        $timestamp = now()->format('YmdHis');
        $password = base64_encode(
            config('mpesa.shortcode').config('mpesa.passkey').$timestamp
        );

        $payload = [
            'BusinessShortCode' => config('mpesa.shortcode'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $payment->amount,
            'PartyA' => $phone,
            'PartyB' => config('mpesa.shortcode'),
            'PhoneNumber' => $phone,
            'CallBackURL' => config('mpesa.callback_url'),
            'AccountReference' => substr($payment->reference, 0, 12),
            'TransactionDesc' => substr('Fee Payment ' . $payment->reference, 0, 13),
        ];

        try {
            $token = $this->getAccessToken();
            $response = Http::acceptJson()->timeout(config('mpesa.timeout'))->withToken($token)
                ->post(config('mpesa.base_url').'/mpesa/stkpush/v1/processrequest', $payload);
        } catch (RequestException $exception) {
            Log::error('M-Pesa STK Push request failed', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);

            return $this->failPayment($payment, 'Could not reach M-Pesa. Please try again.');
        }

        $data = $response->json();
        Log::info('M-Pesa STK Push response', $data ?? []);

        if ($response->successful() && ($data['ResponseCode'] ?? '') === '0') {
            $payment->update([
                'status' => PaymentStatus::Processing,
                'mpesa_checkout_request_id' => $data['CheckoutRequestID'],
                'gateway_response' => $data,
            ]);

            return [
                'success' => true,
                'message' => $data['CustomerMessage'] ?? 'STK Push sent.',
                'checkout_request_id' => $data['CheckoutRequestID'],
            ];
        }

        return $this->failPayment($payment, $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'STK Push failed.', $data);
    }

    public function handleStkCallback(array $payload): void
    {
        $result = $payload['Body']['stkCallback'] ?? [];
        $checkoutId = $result['CheckoutRequestID'] ?? null;
        $resultCode = $result['ResultCode'] ?? 1;

        $payment = Payment::where('mpesa_checkout_request_id', $checkoutId)->first();
        if (! $payment) {
            Log::warning('M-Pesa STK callback could not be matched', ['checkout_request_id' => $checkoutId]);
            return;
        }

        if (in_array($payment->status, [PaymentStatus::Completed, PaymentStatus::Failed])) {
            return;
        }

        if ((int) $resultCode === 0) {
            $metadata = collect($result['CallbackMetadata']['Item'] ?? [])->pluck('Value', 'Name');
            $payment->update([
                'status' => PaymentStatus::Completed,
                'mpesa_receipt' => $receipt = $metadata->get('MpesaReceiptNumber'),
                'paid_at' => now(),
                'gateway_response' => $result,
            ]);

            if ($payment->studentProfile && $payment->studentProfile->user) {
                $this->notifications->notifyPaymentConfirmation(
                    $payment->studentProfile->user,
                    $payment->reference,
                    (string) $payment->amount,
                    $receipt
                );
            }

            $this->processApplicationFee($payment);
        } else {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'gateway_response' => $result,
            ]);
        }
    }

    public function handleC2bConfirmation(array $payload): void
    {
        $reference = $payload['BillRefNumber'] ?? null;
        $receipt = $payload['TransID'] ?? null;
        $amount = $payload['TransAmount'] ?? 0;

        $payment = Payment::where('reference', $reference)
            ->where('method', PaymentMethod::MpesaC2b)
            ->where('status', PaymentStatus::Pending)
            ->first();

        if (! $payment) {
            Log::warning('C2B payment not matched', $payload);

            return;
        }

        if ((float) $amount >= (float) $payment->amount) {
            $payment->update([
                'status' => PaymentStatus::Completed,
                'mpesa_receipt' => $receipt,
                'paid_at' => now(),
                'gateway_response' => $payload,
            ]);

            if ($payment->studentProfile && $payment->studentProfile->user) {
                $this->notifications->notifyPaymentConfirmation(
                    $payment->studentProfile->user,
                    $payment->reference,
                    (string) $payment->amount,
                    $receipt
                );
            }
            
            $this->processApplicationFee($payment);
        }
    }

    public function c2bValidationResponse(array $payload): array
    {
        Log::info('M-Pesa C2B validation received', [
            'transaction_id' => $payload['TransID'] ?? null,
            'reference' => $payload['BillRefNumber'] ?? null,
        ]);

        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    private function getAccessToken(): string
    {
        return Cache::remember('mpesa_access_token', 3500, function () {
            $response = Http::acceptJson()->timeout(config('mpesa.timeout'))->withBasicAuth(
                config('mpesa.consumer_key'),
                config('mpesa.consumer_secret')
            )->get(config('mpesa.base_url').'/oauth/v1/generate?grant_type=client_credentials')->throw();

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new \RuntimeException('M-Pesa did not return an access token. Check your Daraja credentials.');
            }

            return $token;
        });
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }
        if (! str_starts_with($phone, '254')) {
            $phone = '254'.$phone;
        }

        if (! preg_match('/^254(7|1)\d{8}$/', $phone)) {
            throw new \InvalidArgumentException('Enter a valid Kenyan M-Pesa number, for example 0712345678.');
        }

        return $phone;
    }

    private function ensureStkConfiguration(): void
    {
        foreach (['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'callback_url'] as $key) {
            if (blank(config("mpesa.{$key}"))) {
                throw new \RuntimeException('M-Pesa is not configured. Add the Daraja credentials and callback URL to the environment settings.');
            }
        }
    }

    private function failPayment(Payment $payment, string $message, ?array $response = null): array
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'gateway_response' => $response ?? ['error' => $message],
        ]);

        return ['success' => false, 'message' => $message];
    }

    private function processApplicationFee(Payment $payment): void
    {
        if ($payment->feeStructure && $payment->feeStructure->fee_type === 'application') {
            $application = \App\Models\Application::where('student_profile_id', $payment->student_profile_id)
                ->where('programme_id', $payment->feeStructure->programme_id)
                ->where('intake_id', $payment->feeStructure->intake_id)
                ->where('status', \App\Enums\ApplicationStatus::PendingFee)
                ->first();

            if ($application) {
                $application->update([
                    'status' => \App\Enums\ApplicationStatus::Submitted,
                    'submitted_at' => now(),
                ]);

                if ($payment->studentProfile && $payment->studentProfile->user) {
                    $this->notifications->notifyApplicationStatus(
                        $payment->studentProfile->user,
                        $application->status->label(),
                        $application->reference
                    );
                }
            }
        }
    }
}
