<?php

namespace App\Service\BankAccount;

use App\Enum\BankCodeEnum;
use App\Models\BankAccount;
use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    use ServiceResponse;

    /**
     * Daftarkan rekening bank baru milik user.
     */
    public function store(array $data, string $userId): array
    {
        // Validasi bank_code termasuk yang didukung
        $validCodes = array_column(BankCodeEnum::cases(), 'value');
        if (! in_array($data['bank_code'], $validCodes)) {
            throw ValidationException::withMessages([
                'bank_code' => ['Bank yang dipilih tidak didukung.'],
            ]);
        }

        $bankEnum = BankCodeEnum::from($data['bank_code']);

        // Cek apakah nomor rekening ini sudah terdaftar untuk user yang sama
        $alreadyExists = BankAccount::where('user_id', $userId)
            ->where('account_number', $data['account_number'])
            ->where('bank_code', $data['bank_code'])
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'account_number' => ['Rekening ini sudah terdaftar.'],
            ]);
        }

        // Hitung apakah ini rekening pertama user — jika ya, jadikan primary otomatis
        $isFirst = ! BankAccount::where('user_id', $userId)->exists();
        $isPrimary = $isFirst || ($data['is_primary'] ?? false);

        $bankAccount = DB::transaction(function () use ($data, $userId, $bankEnum, $isPrimary) {
            // Jika set as primary, unset primary lama dulu
            if ($isPrimary) {
                BankAccount::where('user_id', $userId)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return BankAccount::create([
                'user_id' => $userId,
                'bank_code' => $bankEnum->value,
                'bank_name' => $bankEnum->label(),
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'is_primary' => $isPrimary,
                'is_verified' => false, // belum diverifikasi
            ]);
        });

        return $this->successPayload($bankAccount, 'Rekening bank berhasil ditambahkan.', 201);
    }

    /**
     * Tampilkan semua rekening bank milik user.
     */
    public function index(string $userId): array
    {
        $accounts = BankAccount::where('user_id', $userId)
            ->orderByDesc('is_primary')
            ->orderBy('created_at')
            ->get();

        return $this->successPayload($accounts);
    }

    /**
     * Set rekening sebagai primary.
     */
    public function setPrimary(string $bankAccountId, string $userId): array
    {
        $bankAccount = BankAccount::where('id', $bankAccountId)
            ->where('user_id', $userId)
            ->first();

        if (! $bankAccount) {
            throw ValidationException::withMessages([
                'bank_account_id' => ['Rekening tidak ditemukan.'],
            ]);
        }

        DB::transaction(function () use ($bankAccount, $userId) {
            // Unset primary lama
            BankAccount::where('user_id', $userId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            // Set primary baru
            $bankAccount->update(['is_primary' => true]);
        });

        return $this->successPayload($bankAccount->fresh(), 'Rekening utama berhasil diperbarui.');
    }

    /**
     * Hapus rekening bank.
     */
    public function destroy(string $bankAccountId, string $userId): array
    {
        $bankAccount = BankAccount::where('id', $bankAccountId)
            ->where('user_id', $userId)
            ->first();

        if (! $bankAccount) {
            throw ValidationException::withMessages([
                'bank_account_id' => ['Rekening tidak ditemukan.'],
            ]);
        }

        $wasPrimary = $bankAccount->is_primary;
        $bankAccount->delete();

        // Jika yang dihapus adalah primary, otomatis set yang terlama sebagai primary baru
        if ($wasPrimary) {
            $next = BankAccount::where('user_id', $userId)
                ->orderBy('created_at')
                ->first();

            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return $this->successPayload(null, 'Rekening bank berhasil dihapus.');
    }

    /**
     * Kembalikan daftar bank yang didukung (untuk dropdown di frontend/mobile).
     */
    public function supportedBanks(): array
    {
        return $this->successPayload(BankCodeEnum::list(), 'Daftar bank yang didukung.');
    }
}
