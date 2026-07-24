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

        $callbackUrl = $this->resolveCallbackUrl();

        $payload = [
            'BusinessShortCode' => config('mpesa.shortcode'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $payment->amount,
            'PartyA' => $phone,
            'PartyB' => config('mpesa.shortcode'),
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => substr($payment->reference, 0, 12),
            'TransactionDesc' => substr('Fee Payment ' . $payment->reference, 0, 13),
        ];

        Log::info('M-Pesa STK Push initiating', [
            'payment_id' => $payment->id,
            'callback_url' => $callbackUrl,
        ]);

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
        Log::info('M-Pesa STK callback received', $payload);

        $result = $payload['Body']['stkCallback'] ?? $payload['stkCallback'] ?? [];
        $checkoutId = $result['CheckoutRequestID'] ?? null;
        $resultCode = $result['ResultCode'] ?? null;

        if (! $checkoutId) {
            Log::warning('M-Pesa STK callback missing CheckoutRequestID', $payload);

            return;
        }

        $payment = Payment::where('mpesa_checkout_request_id', $checkoutId)->first();
        if (! $payment) {
            Log::warning('M-Pesa STK callback could not be matched', ['checkout_request_id' => $checkoutId]);

            return;
        }

        $this->applyStkResult($payment, $result, (int) ($resultCode ?? 1));
    }

    /**
     * Query Daraja for STK status when the async callback is delayed or missed (common on Railway).
     */
    public function queryStkStatus(Payment $payment): array
    {
        if ($payment->status === PaymentStatus::Completed) {
            return ['success' => true, 'status' => 'completed', 'message' => 'Payment already completed.'];
        }

        if ($payment->status === PaymentStatus::Failed) {
            return ['success' => false, 'status' => 'failed', 'message' => 'Payment previously failed. Please try again.'];
        }

        if (blank($payment->mpesa_checkout_request_id)) {
            $this->cancelStalePayment($payment, 'Missing M-Pesa checkout reference.');

            return ['success' => false, 'status' => 'failed', 'message' => 'Payment attempt was incomplete. You can pay again.'];
        }

        // STK prompts expire quickly; don't leave payments blocking retries forever.
        if ($this->expireIfTimedOut($payment)) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'M-Pesa prompt timed out. You can pay again.',
            ];
        }

        try {
            $this->ensureStkConfiguration();
            $timestamp = now()->format('YmdHis');
            $password = base64_encode(
                config('mpesa.shortcode').config('mpesa.passkey').$timestamp
            );

            $token = $this->getAccessToken();
            $response = Http::acceptJson()->timeout(config('mpesa.timeout'))->withToken($token)
                ->post(config('mpesa.base_url').'/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => config('mpesa.shortcode'),
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $payment->mpesa_checkout_request_id,
                ]);
        } catch (\Throwable $exception) {
            Log::error('M-Pesa STK query failed', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            if ($this->expireIfTimedOut($payment)) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'M-Pesa prompt timed out. You can pay again.',
                ];
            }

            return ['success' => false, 'status' => 'processing', 'message' => 'Could not verify payment with M-Pesa yet. Please wait and try again.'];
        }

        $data = $response->json() ?? [];
        Log::info('M-Pesa STK query response', ['payment_id' => $payment->id, 'response' => $data]);

        $resultCode = $data['ResultCode'] ?? null;
        if ($resultCode === null) {
            $errorMessage = strtolower((string) ($data['errorMessage'] ?? $data['ResponseDescription'] ?? ''));
            if (str_contains($errorMessage, 'transaction has expired')
                || str_contains($errorMessage, 'the transaction is invalid')
                || str_contains($errorMessage, 'does not exist')
                || ($data['errorCode'] ?? null) === '500.001.1001') {
                $this->failPayment($payment, $data['errorMessage'] ?? 'M-Pesa transaction expired. Please pay again.', $data);

                return ['success' => false, 'status' => 'failed', 'message' => 'M-Pesa transaction expired. You can pay again.'];
            }

            if ($this->expireIfTimedOut($payment)) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'M-Pesa prompt timed out. You can pay again.',
                ];
            }

            return [
                'success' => true,
                'status' => 'processing',
                'message' => $data['ResponseDescription'] ?? $data['errorMessage'] ?? 'Payment is still processing on M-Pesa.',
            ];
        }

        $code = (int) $resultCode;

        // Still pending on the phone
        if (in_array($code, [4999, 1037], true)) {
            if ($this->expireIfTimedOut($payment)) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'M-Pesa prompt timed out. You can pay again.',
                ];
            }

            return [
                'success' => true,
                'status' => 'processing',
                'message' => $data['ResultDesc'] ?? 'Waiting for you to complete the M-Pesa prompt.',
            ];
        }

        $this->applyStkResult($payment, $data, $code);
        $payment->refresh();

        if ($payment->status === PaymentStatus::Completed) {
            return ['success' => true, 'status' => 'completed', 'message' => 'Payment confirmed successfully.'];
        }

        if ($payment->status === PaymentStatus::Failed) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $data['ResultDesc'] ?? 'Payment was not completed on M-Pesa.',
            ];
        }

        return [
            'success' => true,
            'status' => 'processing',
            'message' => $data['ResultDesc'] ?? 'Payment is still processing.',
        ];
    }

    /**
     * Mark abandoned STK attempts as failed so students can retry the same fee.
     * Safaricom prompts typically expire within about a minute.
     */
    public function expireIfTimedOut(Payment $payment, int $seconds = 90): bool
    {
        if ($payment->status !== PaymentStatus::Processing) {
            return false;
        }

        $startedAt = $payment->updated_at ?? $payment->created_at;
        if (! $startedAt || $startedAt->gt(now()->subSeconds($seconds))) {
            return false;
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'gateway_response' => array_merge(
                is_array($payment->gateway_response) ? $payment->gateway_response : [],
                ['local_timeout' => true, 'message' => 'STK prompt timed out locally after '.$seconds.' seconds.']
            ),
        ]);

        return true;
    }

    public function cancelStalePayment(Payment $payment, string $reason = 'Cancelled by student to retry payment.'): void
    {
        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'gateway_response' => array_merge(
                is_array($payment->gateway_response) ? $payment->gateway_response : [],
                ['cancelled' => true, 'message' => $reason]
            ),
        ]);
    }

    private function applyStkResult(Payment $payment, array $result, int $resultCode): void
    {
        if (in_array($payment->status, [PaymentStatus::Completed, PaymentStatus::Failed], true)) {
            return;
        }

        if ($resultCode === 0) {
            $metadata = collect($result['CallbackMetadata']['Item'] ?? [])->pluck('Value', 'Name');
            $receipt = $metadata->get('MpesaReceiptNumber')
                ?: ($result['MpesaReceiptNumber'] ?? $payment->mpesa_receipt);

            $payment->update([
                'status' => PaymentStatus::Completed,
                'mpesa_receipt' => $receipt,
                'paid_at' => now(),
                'gateway_response' => $result,
            ]);

            if ($payment->studentProfile && $payment->studentProfile->user) {
                $this->notifications->notifyPaymentConfirmation(
                    $payment->studentProfile->user,
                    $payment->reference,
                    (string) $payment->amount,
                    $receipt ?? 'N/A'
                );
            }

            $this->processApplicationFee($payment);

            return;
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'gateway_response' => $result,
        ]);
    }

    public function handleC2bConfirmation(array $payload): void
    {
        Log::info('M-Pesa C2B confirmation received', $payload);
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
        foreach (['consumer_key', 'consumer_secret', 'shortcode', 'passkey'] as $key) {
            if (blank(config("mpesa.{$key}"))) {
                throw new \RuntimeException('M-Pesa is not configured. Add the Daraja credentials to the environment settings.');
            }
        }

        // Resolve early so misconfigured Railway APP_URL fails with a clear message.
        $this->resolveCallbackUrl();
    }

    /**
     * Daraja requires a public HTTPS callback. Railway often leaves
     * MPESA_CALLBACK_URL="${APP_URL}/..." unexpanded, or APP_URL as http://.
     */
    private function resolveCallbackUrl(): string
    {
        $candidates = [
            config('mpesa.callback_url'),
            $this->publicAppUrl() ? rtrim($this->publicAppUrl(), '/').'/api/mpesa/stk-callback' : null,
            url('/api/mpesa/stk-callback'),
        ];

        foreach ($candidates as $candidate) {
            $url = $this->normalizePublicHttpsUrl($candidate);
            if ($url) {
                return $url;
            }
        }

        throw new \RuntimeException(
            'Invalid M-Pesa callback URL. On Railway set APP_URL and MPESA_CALLBACK_URL to your public HTTPS domain, '
            .'for example https://your-app.up.railway.app/api/mpesa/stk-callback (do not use ${APP_URL} in the Railway dashboard).'
        );
    }

    private function publicAppUrl(): ?string
    {
        $railwayDomain = env('RAILWAY_PUBLIC_DOMAIN') ?: env('RAILWAY_STATIC_URL');
        if (filled($railwayDomain)) {
            $host = preg_replace('#^https?://#', '', rtrim((string) $railwayDomain, '/'));

            return 'https://'.$host;
        }

        $appUrl = (string) config('app.url');

        return $this->normalizePublicHttpsUrl($appUrl) ? $this->ensureHttps((string) $appUrl) : null;
    }

    private function normalizePublicHttpsUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim((string) $url);

        // Unexpanded Railway/dotenv placeholders are not valid callbacks.
        if (str_contains($url, '${') || str_contains($url, '%7B') || str_contains($url, '{{')) {
            return null;
        }

        $url = $this->ensureHttps($url);

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        if ($parts['scheme'] !== 'https') {
            return null;
        }

        $host = strtolower($parts['host']);
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            // Local-only: allow sandbox dummy so STK can be initiated during local testing.
            if (config('mpesa.environment') === 'sandbox' && app()->environment('local')) {
                return 'https://sandbox.safaricom.co.ke/mpesa/c2bconfirmation';
            }

            return null;
        }

        // Private / internal hosts are rejected by Daraja.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        return $url;
    }

    private function ensureHttps(string $url): string
    {
        $url = trim($url);
        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, 7);
        }

        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
            return 'https://'.ltrim($url, '/');
        }

        return $url;
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
        app(\App\Services\Applications\ApplicationFeeService::class)
            ->processCompletedPayment($payment);
    }
}
