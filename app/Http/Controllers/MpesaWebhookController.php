<?php

namespace App\Http\Controllers;

use App\Services\Payments\MpesaService;
use Illuminate\Http\Request;

class MpesaWebhookController extends Controller
{
    public function __construct(private MpesaService $mpesa) {}

    public function stkCallback(Request $request)
    {
        try {
            $this->mpesa->handleStkCallback($request->all());
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('M-Pesa STK callback handling failed', [
                'error' => $exception->getMessage(),
                'payload' => $request->all(),
            ]);
        }

        // Always acknowledge so Safaricom does not retry forever.
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function c2bConfirmation(Request $request)
    {
        $this->mpesa->handleC2bConfirmation($request->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function c2bValidation(Request $request)
    {
        return response()->json($this->mpesa->c2bValidationResponse($request->all()));
    }
}
