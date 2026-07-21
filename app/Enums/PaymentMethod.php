<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MpesaStk = 'mpesa_stk';
    case MpesaC2b = 'mpesa_c2b';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::MpesaStk => 'M-Pesa STK Push',
            self::MpesaC2b => 'M-Pesa Paybill',
            self::BankTransfer => 'Bank Transfer',
        };
    }
}
