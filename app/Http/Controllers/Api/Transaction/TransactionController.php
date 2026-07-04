<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Service\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of the user's transactions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $result = $this->transactionService->getUsersTransactions(
            auth('sanctum')->id(),
            $status
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Complete a transaction by the helper.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'completion_notes' => 'required|string|max:2000',
            'completion_images' => 'required|array|min:1',
            'completion_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = $request->file('completion_images');

        $result = $this->transactionService->completeTransaction(
            $id,
            auth('sanctum')->id(),
            $data,
            $uploadedImages
        );

        return response()->json($result, $result['code']);
    }
}
