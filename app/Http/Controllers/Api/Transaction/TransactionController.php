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

    public function activeTransactions(Request $request): JsonResponse
    {
        $result = $this->transactionService->getActiveTransactions(
            auth('sanctum')->id()
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
            'revision_images' => 'sometimes|array|max:5',
            'revision_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = $request->file('revision_images') ?? [];

        $result = $this->transactionService->requestRevision(
            $id,
            auth('sanctum')->id(),
            $data,
            $uploadedImages
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Respond to a transaction revision (Accept/Fix or Reject/Dispute).
     */
    public function respondRevision(Request $request, string $revisionId): JsonResponse
    {
        $data = $request->validate([
            'action'           => 'required|string|in:accepted,fixed,rejected',
            'revision_deadline' => 'required_if:action,accepted|nullable|date|after:now',
            'completion_notes' => 'required_if:action,fixed|nullable|string|max:2000',
            'completion_images' => 'required_if:action,fixed|nullable|array|min:1',
            'completion_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'dispute_reason'   => 'required_if:action,rejected|nullable|string|max:2000',
            'dispute_images'   => 'required_if:action,rejected|nullable|array|min:1',
            'dispute_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = [];
        if ($data['action'] === 'fixed') {
            $uploadedImages = $request->file('completion_images') ?? [];
        } else if ($data['action'] === 'rejected') {
            $uploadedImages = $request->file('dispute_images') ?? [];
        }

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
            'refund_images' => 'sometimes|array',
            'refund_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = $request->file('refund_images') ?? [];

        $result = $this->transactionService->requestRefund(
            $id,
            auth('sanctum')->id(),
            $data,
            $uploadedImages
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

    /**
     * Get transaction history that has been reviewed.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reviewedHistory(Request $request): JsonResponse
    {
        $userId = $request->query('user_id') ?? auth('sanctum')->id();
        $result = $this->transactionService->getReviewedTransactions($userId);

        return response()->json($result, $result['code']);
    }

    /**
     * Submit a review for a transaction.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function review(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = $request->file('images') ?? [];

        $result = $this->transactionService->createReview(
            $id,
            auth('sanctum')->id(),
            $data,
            $uploadedImages
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Disburse/transfer funds to Helper's primary bank account.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function transfer(Request $request, string $id): JsonResponse
    {
        $result = $this->transactionService->disburseToHelper($id);

        return response()->json($result, $result['code']);
    }

    /**
     * Update a transaction's status, completion notes, finished_at, and completion images.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'sometimes|string|in:pending,on_progress,pending_approval,pending_revision,completed,disputed,cancelled',
            'completion_notes' => 'sometimes|nullable|string|max:2000',
            'finished_at' => 'sometimes|nullable|date',
            'completion_images' => 'sometimes|array|min:1',
            'completion_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = $request->file('completion_images') ?? [];

        $result = $this->transactionService->updateTransaction($id, $data, $uploadedImages);

        return response()->json($result, $result['code']);
    }

    /**
     * Cancel a transaction manually.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $result = $this->transactionService->cancelTransaction(
            $id,
            auth('sanctum')->id()
        );

        return response()->json($result, $result['code']);
    }

    /**
     * Get a transaction by its ID.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getById(string $id): JsonResponse
    {
        $result = $this->transactionService->getTransactionById($id);

        return response()->json($result, $result['code']);
    }
}



