<?php

namespace App\Http\Controllers\Api\Payment;

use App\Enum\BankCodeEnum;
use App\Http\Controllers\Controller;
use App\Service\Payment\PaymentService;
use App\Traits\ServiceResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ServiceResponse;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * POST /payments
     * Requester membuat payment untuk offer yang sudah diterima.
     * Mengembalikan snap_token untuk dipakai di Midtrans Snap.js (frontend/mobile).
     */
    public function create(Request $request): JsonResponse
    {
        $validBanks = array_column(BankCodeEnum::cases(), 'value');

        $data = $request->validate([
            'offer_id' => ['required', 'exists:offers,id'],
            'bank'     => ['required', 'string', 'in:' . implode(',', $validBanks)],
        ]);

        $result = $this->paymentService->createPayment(
            $data['offer_id'],
            $data['bank'],
            auth('sanctum')->id()
        );

        return response()->json($result, $result['code']);
    }

    /**
     * GET /payments/transactions/{transactionId}
     * Cek status payment dan escrow untuk sebuah transaksi.
     */
    public function status(string $transactionId): JsonResponse
    {
        $result = $this->paymentService->getPaymentStatus(
            $transactionId,
            auth('sanctum')->id()
        );

        return response()->json($result, $result['code']);
    }

    /**
     * POST /payments/webhook
     * Endpoint yang dipanggil Midtrans saat ada notifikasi pembayaran.
     * PENTING: route ini TIDAK pakai middleware auth:sanctum
     *          tapi menggunakan verifikasi signature dari Midtrans.
     */
    public function webhook(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleWebhook();

        return response()->json($result, $result['code']);
    }
}
