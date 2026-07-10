<?php

namespace App\Service\Payment;

use App\Models\EscrowTransaction;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Transaction;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\CoreApi;

class PaymentService
{
    use ServiceResponse;

    public function __construct()
    {
        $this->configureMidtrans();
    }

    // -------------------------------------------------------------------------
    // 1. Buat Payment + Snap Token Midtrans
    // -------------------------------------------------------------------------

    /**
     * Requester memulai pembayaran untuk offer yang sudah diterima.
     * Ini akan membuat Transaction, Payment, dan mengembalikan snap token Midtrans.
     *
     * @param  string $offerId   ID offer yang sudah diterima
     * @param  string $bank      Kode bank VA yang dipilih (bca, bni, bri, dll)
     * @param  string $userId    ID requester yang login
     */
    public function createPayment(string $offerId, string $bank, string $userId): array
    {
        $offer = Offer::with(['post', 'requester', 'helper'])->findOrFail($offerId);

        // Pastikan yang bayar adalah requester dari offer
        if ($offer->requester_id !== $userId) {
            throw ValidationException::withMessages([
                'offer_id' => ['Kamu tidak berhak membayar offer ini.'],
            ]);
        }

        // Pastikan offer sudah diterima
        if ($offer->status !== 'accepted') {
            throw ValidationException::withMessages([
                'offer_id' => ['Hanya offer yang sudah diterima yang bisa dibayar.'],
            ]);
        }

        // Cek apakah sudah ada transaksi untuk offer ini
        $existingTransaction = Transaction::where('offer_id', $offerId)->first();
        if ($existingTransaction) {
            $existingPayment = $existingTransaction->payment;
            if ($existingPayment && in_array($existingPayment->status, ['pending', 'completed'])) {
                throw ValidationException::withMessages([
                    'offer_id' => ['Transaksi untuk offer ini sudah ada.'],
                ]);
            }

            // Gunakan harga dari transaksi yang sudah disepakati di final agreement
            $finalPrice = $existingTransaction->final_price;
            $adminFee   = $existingTransaction->admin_fee;
            $totalPrice = $existingTransaction->total_price;
        } else {
            // Hitung harga default jika transaksi belum terbuat (fallback)
            $finalPrice  = $offer->offered_price;
            $feePercent  = config('midtrans.platform_fee_percent', 0.05);
            $adminFee    = round($finalPrice * $feePercent);
            $totalPrice  = $finalPrice + $adminFee;
        }

        return DB::transaction(function () use ($offer, $bank, $finalPrice, $adminFee, $totalPrice, $existingTransaction) {
            // Gunakan transaksi yang sudah ada atau buat baru jika tidak ada
            $transaction = $existingTransaction;
            if (!$transaction) {
                $transaction = Transaction::create([
                    'offer_id'     => $offer->id,
                    'requester_id' => $offer->requester_id,
                    'helper_id'    => $offer->helper_id,
                    'final_price'  => $finalPrice,
                    'admin_fee'    => $adminFee,
                    'total_price'  => $totalPrice,
                    'deadline'     => $offer->post?->requestDetail?->deadline ?? now()->addDays(7),
                    'status'       => 'pending',
                ]);
            }

            // Buat Payment record
            $midtransOrderId = 'BANTUIN-' . strtoupper(substr($transaction->id, 0, 8)) . '-' . time();

            $payment = Payment::create([
                'transaction_id'   => $transaction->id,
                'amount'           => $totalPrice,
                'payment_method'   => 'bank_transfer',
                'bank'             => $bank,
                'midtrans_order_id' => $midtransOrderId,
                'status'           => 'pending',
            ]);

            // Buat Snap Token Midtrans
            $snapToken = $this->createSnapToken($transaction, $payment, $offer, $bank);

            // Simpan snap token
            $payment->update(['snap_token' => $snapToken]);

            return $this->successPayload([
                'transaction'       => $transaction,
                'payment'           => $payment->fresh(),
                'snap_token'        => $snapToken,
                'midtrans_order_id' => $midtransOrderId,
                'amount'            => $totalPrice,
                'breakdown' => [
                    'service_price' => $finalPrice,
                    'platform_fee'  => $adminFee,
                    'total'         => $totalPrice,
                ],
            ], 'Payment berhasil dibuat. Lanjutkan ke halaman pembayaran Midtrans.', 201);
        });
    }

    // -------------------------------------------------------------------------
    // 2. Handle Webhook Notifikasi dari Midtrans
    // -------------------------------------------------------------------------

    /**
     * Dipanggil dari webhook endpoint saat Midtrans mengirim notifikasi payment.
     * Otomatis mengelola status payment dan membuat escrow saat pembayaran berhasil.
     */
    public function handleWebhook(): array
    {
        $this->configureMidtrans();

        $rawBody = request()->getContent();
        Log::info('RAW WEBHOOK BODY:', ['body' => $rawBody]);

        $rawNotification = json_decode($rawBody, true);
        if (!$rawNotification) {
            Log::warning('Midtrans webhook: empty or invalid JSON payload');
            return $this->errorPayload('Invalid payload', [], 400);
        }

        $orderId           = $rawNotification['order_id'] ?? null;
        $transactionStatus = $rawNotification['transaction_status'] ?? null;
        $fraudStatus       = $rawNotification['fraud_status'] ?? null;
        $paymentType       = $rawNotification['payment_type'] ?? null;

        $vaNumber = null;
        if (isset($rawNotification['va_numbers']) && is_array($rawNotification['va_numbers'])) {
            $vaNumber = $rawNotification['va_numbers'][0]['va_number'] ?? null;
        } elseif (isset($rawNotification['permata_va_number'])) {
            $vaNumber = $rawNotification['permata_va_number'];
        } elseif (isset($rawNotification['bill_key'])) {
            $vaNumber = ($rawNotification['biller_code'] ?? '') . '-' . $rawNotification['bill_key'];
        }

        Log::info('Midtrans webhook received', [
            'order_id' => $orderId,
            'status'   => $transactionStatus,
            'fraud'    => $fraudStatus,
        ]);

        $payment = Payment::where('midtrans_order_id', $orderId)
            ->with('transaction')
            ->first();

        if (!$payment) {
            Log::warning('Midtrans webhook: payment not found', ['order_id' => $orderId]);
            return $this->errorPayload('Payment tidak ditemukan.', [], 404);
        }

        // Jika sudah completed, skip (idempotent)
        if ($payment->status === 'completed') {
            return $this->successPayload(null, 'Sudah diproses.');
        }

        DB::transaction(function () use ($payment, $transactionStatus, $fraudStatus, $vaNumber) {
            $transaction = $payment->transaction;

            // Simpan nomor VA jika ada
            if ($vaNumber) {
                $payment->update(['va_number' => $vaNumber]);
            }

            if ($transactionStatus === 'capture') {
                // Kartu kredit
                if ($fraudStatus === 'accept') {
                    $this->markPaymentSuccess($payment, $transaction);
                }
            } elseif ($transactionStatus === 'settlement') {
                // Bank transfer / VA berhasil settlement
                $this->markPaymentSuccess($payment, $transaction);
            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $payment->update(['status' => 'failed']);
                $transaction->update(['status' => 'cancelled']);
            } elseif ($transactionStatus === 'pending') {
                // Tidak perlu update, sudah pending dari awal
            }
        });

        return $this->successPayload(null, 'Notifikasi berhasil diproses.');
    }

    // -------------------------------------------------------------------------
    // 3. Status Pembayaran
    // -------------------------------------------------------------------------

    /**
     * Cek status payment berdasarkan transaction ID.
     */
    public function getPaymentStatus(string $transactionId, string $userId): array
    {
        $transaction = Transaction::with(['payment', 'escrow', 'requester', 'helper'])
            ->where('id', $transactionId)
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('helper_id', $userId);
            })
            ->first();

        if (!$transaction) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Transaksi tidak ditemukan.'],
            ]);
        }

        $payment = $transaction->payment;
        if ($payment && $payment->status === 'pending') {
            $this->configureMidtrans();
            try {
                // Tanya langsung ke API Midtrans mengenai status transaksi ter-update
                $midtransStatus = \Midtrans\Transaction::status($payment->midtrans_order_id);
                $midtransStatusArray = json_decode(json_encode($midtransStatus), true);

                $transactionStatus = $midtransStatusArray['transaction_status'] ?? null;
                $fraudStatus       = $midtransStatusArray['fraud_status'] ?? null;
                $vaNumber          = null;

                if (isset($midtransStatusArray['va_numbers']) && is_array($midtransStatusArray['va_numbers']) && count($midtransStatusArray['va_numbers']) > 0) {
                    $vaNumber = $midtransStatusArray['va_numbers'][0]['va_number'] ?? null;
                } elseif (isset($midtransStatusArray['permata_va_number'])) {
                    $vaNumber = $midtransStatusArray['permata_va_number'];
                } elseif (isset($midtransStatusArray['bill_key'])) {
                    $vaNumber = ($midtransStatusArray['biller_code'] ?? '') . '-' . $midtransStatusArray['bill_key'];
                }

                // Update nomor VA jika belum ada
                if ($vaNumber && !$payment->va_number) {
                    $payment->update(['va_number' => $vaNumber]);
                }

                // Jika statusnya settlement atau capture (CC) yang diterima, update database lokal
                if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                    DB::transaction(function () use ($payment, $transaction) {
                        $this->markPaymentSuccess($payment, $transaction);
                    });
                    
                    // Refresh data transaksi setelah di-update
                    $transaction->refresh();
                } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                    DB::transaction(function () use ($payment, $transaction) {
                        $payment->update(['status' => 'failed']);
                        $transaction->update(['status' => 'cancelled']);
                    });
                    $transaction->refresh();
                }
            } catch (\Exception $e) {
                Log::warning("Gagal fetch status dari Midtrans API: " . $e->getMessage());
            }
        }

        return $this->successPayload($transaction);
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Set payment sebagai berhasil dan otomatis buat record escrow.
     */
    private function markPaymentSuccess(Payment $payment, Transaction $transaction): void
    {
        $payment->update([
            'status'  => 'completed',
            'paid_at' => now(),
        ]);

        $transaction->update([
            'status'     => 'on_progress',
            'started_at' => now(),
        ]);

        // Otomatis hold dana ke escrow
        EscrowTransaction::create([
            'transaction_id' => $transaction->id,
            'payment_id'     => $payment->id,
            'held_amount'    => $payment->amount,
            'fee_amount'     => $transaction->admin_fee,
            'net_amount'     => $transaction->final_price,
            'status'         => 'held',
            'held_at'        => now(),
        ]);

        Log::info('Escrow created', ['transaction_id' => $transaction->id]);
    }

    /**
     * Buat Snap Token dari Midtrans.
     */
    private function createSnapToken(Transaction $transaction, Payment $payment, Offer $offer, ?string $bank = null): string
    {
        $requester = $offer->requester;

        $params = [
            'transaction_details' => [
                'order_id'     => $payment->midtrans_order_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $requester->first_name ?? 'User',
                'last_name'  => $requester->last_name ?? '',
                'email'      => $requester->email,
                'phone'      => $requester->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => $offer->id,
                    'price'    => (int) $transaction->final_price,
                    'quantity' => 1,
                    'name'     => 'Bantuan Jasa - ' . ($offer->post?->title ?? 'Layanan'),
                ],
                [
                    'id'       => 'PLATFORM_FEE',
                    'price'    => (int) $transaction->admin_fee,
                    'quantity' => 1,
                    'name'     => 'Biaya Platform Bantuin',
                ],
            ],
        ];

        // Filter enabled payment method based on selected bank/payment channel
        if ($bank) {
            $bankLower = strtolower($bank);
            if ($bankLower === 'gopay') {
                $params['enabled_payments'] = ['gopay', 'qris'];
            } else if ($bankLower === 'card') {
                $params['enabled_payments'] = ['credit_card'];
            } else if ($bankLower === 'bca') {
                $params['enabled_payments'] = ['bca_va'];
            } else if ($bankLower === 'bni') {
                $params['enabled_payments'] = ['bni_va'];
            } else if ($bankLower === 'bri') {
                $params['enabled_payments'] = ['bri_va'];
            } else if ($bankLower === 'mandiri') {
                $params['enabled_payments'] = ['echannel', 'mandiri_va'];
            } else if ($bankLower === 'permata') {
                $params['enabled_payments'] = ['permata_va'];
            } else {
                $params['enabled_payments'] = [$bankLower . '_va'];
            }
        } else {
            $params['enabled_payments'] = [
                'bca_va', 'bni_va', 'bri_va', 'mandiri_va',
                'cimb_va', 'danamon_va', 'permata_va', 'other_va',
                'gopay', 'qris', 'credit_card'
            ];
        }

        return Snap::getSnapToken($params);
    }

    /**
     * Charge direct bank transfer menggunakan Midtrans Core API.
     */
    private function chargeDirectBankTransfer(Transaction $transaction, Payment $payment, Offer $offer, string $bank): array
    {
        $requester = $offer->requester;

        $params = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id'     => $payment->midtrans_order_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $requester->first_name ?? 'User',
                'last_name'  => $requester->last_name ?? '',
                'email'      => $requester->email,
                'phone'      => $requester->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => $offer->id,
                    'price'    => (int) $transaction->final_price,
                    'quantity' => 1,
                    'name'     => 'Bantuan Jasa - ' . ($offer->post?->title ?? 'Layanan'),
                ],
                [
                    'id'       => 'PLATFORM_FEE',
                    'price'    => (int) $transaction->admin_fee,
                    'quantity' => 1,
                    'name'     => 'Biaya Platform Bantuin',
                ],
            ],
        ];

        if ($bank === 'mandiri') {
            $params['payment_type'] = 'echannel';
            $params['echannel'] = [
                'bill_info1' => 'Bantuin Jasa',
                'bill_info2' => 'Payment for order ' . $payment->midtrans_order_id,
            ];
        } else if ($bank === 'permata') {
            $params['bank_transfer'] = [
                'bank' => 'permata',
            ];
        } else {
            // bca, bni, bri
            $params['bank_transfer'] = [
                'bank' => $bank,
            ];
        }

        $response = CoreApi::charge($params);

        return json_decode(json_encode($response), true);
    }

    /**
     * Set konfigurasi Midtrans dari config file.
     */
    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey    = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized  = config('midtrans.is_sanitized');
        MidtransConfig::$is3ds        = config('midtrans.is_3ds');
    }
}
