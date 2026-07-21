<?php

namespace App\Http\Controllers;

use App\Services\Payments\MpesaService;
use Illuminate\Http\Request;

class MpesaWebhookController extends Controller
{
    public function __construct(private MpesaService $mpesa) {}

    public function stkCallback(Request $request)
    {
        $this->mpesa->handleStkCallback($request->all());

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
