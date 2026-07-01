<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    | Server Key dan Client Key bisa didapat dari:
    | https://dashboard.sandbox.midtrans.com/settings/config_info
    |
    */

    'server_key'     => env('MIDTRANS_SERVER_KEY'),
    'client_key'     => env('MIDTRANS_CLIENT_KEY'),
    'is_production'  => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized'   => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds'         => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Platform Fee (Admin Fee)
    |--------------------------------------------------------------------------
    | Persentase fee platform dari total harga transaksi.
    | Contoh: 0.05 = 5%
    |
    */
    'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 0.05),

    /*
    |--------------------------------------------------------------------------
    | Auto Release (Hari)
    |--------------------------------------------------------------------------
    | Jika requester tidak merespons dalam X hari setelah helper submit,
    | dana di escrow otomatis dilepas ke helper.
    |
    */
    'auto_release_days' => env('ESCROW_AUTO_RELEASE_DAYS', 3),
];
