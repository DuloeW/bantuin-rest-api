<?php

namespace App\Http\Controllers\Api\BankAccount;

use App\Enum\BankCodeEnum;
use App\Http\Controllers\Controller;
use App\Service\BankAccount\BankAccountService;
use App\Traits\ServiceResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    use ServiceResponse;

    protected BankAccountService $bankAccountService;

    public function __construct(BankAccountService $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * GET /bank-accounts
     * Lihat semua rekening bank milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $result = $this->bankAccountService->index(auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }

    /**
     * POST /bank-accounts
     * Tambah rekening bank baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validCodes = array_column(BankCodeEnum::cases(), 'value');

        $data = $request->validate([
            'bank_code'      => ['required', 'string', 'in:' . implode(',', $validCodes)],
            'account_number' => ['required', 'string', 'max:30'],
            'account_name'   => ['required', 'string', 'max:100'],
            'is_primary'     => ['sometimes', 'boolean'],
        ]);

        $result = $this->bankAccountService->store($data, auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }

    /**
     * PATCH /bank-accounts/{id}/primary
     * Set rekening ini sebagai rekening utama (primary).
     */
    public function setPrimary(string $id): JsonResponse
    {
        $result = $this->bankAccountService->setPrimary($id, auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }

    /**
     * DELETE /bank-accounts/{id}
     * Hapus rekening bank.
     */
    public function destroy(string $id): JsonResponse
    {
        $result = $this->bankAccountService->destroy($id, auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }

    /**
     * GET /bank-accounts/supported-banks
     * Daftar bank yang didukung (untuk dropdown di app).
     */
    public function supportedBanks(): JsonResponse
    {
        $result = $this->bankAccountService->supportedBanks();

        return response()->json($result, $result['code']);
    }
}
