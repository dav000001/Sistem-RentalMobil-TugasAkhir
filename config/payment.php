<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rekening Tujuan Transfer Pembayaran Customer
    |--------------------------------------------------------------------------
    */
    'bank_name'    => env('PAYMENT_BANK_NAME', 'BCA'),
    'account_no'   => env('PAYMENT_ACCOUNT_NO', '1234567890'),
    'account_name' => env('PAYMENT_ACCOUNT_NAME', 'PT Rental Mobil Indonesia'),
];
