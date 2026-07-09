<?php

namespace App\Service\Withdrawal;

use App\Models\Withdrawal;
use App\Models\BankAccount;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class WithdrawalService
{
    use ServiceResponse;

    /**
     * Helper requests a withdrawal.
     *
     * @param string $userId
     * @param array $data
     * @return array
     */
    public function requestWithdrawal(string $userId, array $data): array
    {
        try {
            return DB::transaction(function () use ($userId, $data) {
                $user = \App\Models\User::lockForUpdate()->find($userId);

                if (!$user) {
                    throw ValidationException::withMessages([
                        'user' => ['User tidak ditemukan.']
                    ]);
                }

                $amount = $data['amount'];
                
                // Minimal penarikan (misal: 10.000)
                if ($amount < 10000) {
                    throw ValidationException::withMessages([
                        'amount' => ['Minimal penarikan adalah Rp 10.000.']
                    ]);
                }

                if ($user->wallet_balance < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => ['Saldo wallet tidak mencukupi untuk penarikan ini.']
                    ]);
                }

                $bankAccount = BankAccount::where('user_id', $userId)
                                ->where('id', $data['bank_account_id'])
                                ->first();

                if (!$bankAccount) {
                    throw ValidationException::withMessages([
                        'bank_account' => ['Rekening bank tidak ditemukan atau bukan milik Anda.']
                    ]);
                }

                // Cek apakah ada penarikan yang masih pending
                $pendingWithdrawal = Withdrawal::where('user_id', $userId)
                                        ->whereIn('status', ['pending', 'processing'])
                                        ->exists();

                if ($pendingWithdrawal) {
                    throw ValidationException::withMessages([
                        'status' => ['Anda masih memiliki pengajuan penarikan yang sedang diproses.']
                    ]);
                }

                // Kalkulasi admin fee 10%
                $adminFee = $amount * 0.10;
                $netAmount = $amount - $adminFee;

                $withdrawal = Withdrawal::create([
                    'user_id' => $userId,
                    'bank_account_id' => $bankAccount->id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'net_amount' => $netAmount,
                    'status' => 'pending',
                ]);

                return $this->successPayload($withdrawal, 'Pengajuan penarikan berhasil dibuat.', 201);
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Get user's withdrawal history.
     *
     * @param string $userId
     * @return array
     */
    public function getWithdrawalHistory(string $userId): array
    {
        $withdrawals = Withdrawal::with('bankAccount')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return $this->successPayload($withdrawals, 'Riwayat penarikan berhasil diambil.');
    }
}
