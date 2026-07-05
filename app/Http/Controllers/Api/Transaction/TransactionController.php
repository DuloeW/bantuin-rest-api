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

    /**
     * Approve a transaction and release funds.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $result = $this->transactionService->approveTransaction(
            $id,
            auth('sanctum')->id()
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Request a revision for a transaction.
     */
    public function revision(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'revision_notes' => 'required|string|max:2000',
        ]);

        $result = $this->transactionService->requestRevision(
            $id,
            auth('sanctum')->id(),
            $data
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Respond to a transaction revision (Accept/Fix or Reject/Dispute).
     */
    public function respondRevision(Request $request, string $revisionId): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|string|in:fixed,rejected',
            'completion_notes' => 'required_if:action,fixed|nullable|string|max:2000',
            'completion_images' => 'required_if:action,fixed|nullable|array|min:1',
            'completion_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'dispute_reason' => 'required_if:action,rejected|nullable|string|max:2000',
        ]);

        $uploadedImages = $request->file('completion_images') ?? [];

        $result = $this->transactionService->respondToRevision(
            $revisionId,
            auth('sanctum')->id(),
            $data,
            $uploadedImages
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Request a refund for a transaction.
     */
    public function requestRefund(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $result = $this->transactionService->requestRefund(
            $id,
            auth('sanctum')->id(),
            $data
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Respond to a refund request (Approve or Reject).
     */
    public function respondRefund(Request $request, string $refundId): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|string|in:approved,rejected',
            'dispute_reason' => 'required_if:action,rejected|nullable|string|max:2000',
        ]);

        $result = $this->transactionService->respondToRefund(
            $refundId,
            auth('sanctum')->id(),
            $data
        );

        return response()->json($result, $result['code']);
    }
}
