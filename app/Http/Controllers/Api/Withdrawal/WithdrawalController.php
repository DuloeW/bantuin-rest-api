<?php

namespace App\Http\Controllers\Api\Withdrawal;

use App\Http\Controllers\Controller;
use App\Service\Withdrawal\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    protected WithdrawalService $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Request a new withdrawal
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_account_id' => 'required|uuid',
        ]);

        $result = $this->withdrawalService->requestWithdrawal(
            auth('sanctum')->id(),
            $data
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Get user's withdrawal history
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->withdrawalService->getWithdrawalHistory(
            auth('sanctum')->id()
        );

        return response()->json($result, $result['code']);
    }
}
