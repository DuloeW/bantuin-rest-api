<?php
require __DIR__ . '/vendor/autoload.php';

use Midtrans\Config as MidtransConfig;
use Midtrans\CoreApi;

MidtransConfig::$serverKey = env('MIDTRANS_SERVER_KEY');
MidtransConfig::$isProduction = false;
MidtransConfig::$isSanitized = true;
MidtransConfig::$is3ds = true;

$banks = ['bca', 'bni', 'bri', 'permata', 'mandiri'];

foreach ($banks as $bank) {
    try {
        echo "Testing bank: $bank... ";
        $params = [
            'payment_type' => $bank === 'mandiri' ? 'echannel' : 'bank_transfer',
            'transaction_details' => [
                'order_id' => 'TEST-' . strtoupper($bank) . '-' . time(),
                'gross_amount' => 10000,
            ],
        ];

        if ($bank === 'mandiri') {
            $params['echannel'] = [
                'bill_info1' => 'Test',
                'bill_info2' => 'Test'
            ];
        } else {
            $params['bank_transfer'] = [
                'bank' => $bank
            ];
        }

        $charge = CoreApi::charge($params);
        $chargeArray = json_decode(json_encode($charge), true);
        
        echo "ACTIVE! Response: ";
        if ($bank === 'mandiri') {
            echo "Bill Key: " . ($chargeArray['bill_key'] ?? 'N/A') . "\n";
        } elseif ($bank === 'permata') {
            echo "Permata VA: " . ($chargeArray['permata_va_number'] ?? 'N/A') . "\n";
        } else {
            echo "VA: " . ($chargeArray['va_numbers'][0]['va_number'] ?? 'N/A') . "\n";
        }
    } catch (\Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}
