<?php

namespace App\Service\User;

use App\Models\User;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService
{
    use ServiceResponse;

    public function getAllUsers()
    {
        $users = User::all();

        return $this->successPayload($users, 'users retrieved successfully');
    }

    public function getUserById(string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorPayload('user not found', [], 404);
        }

        $user->load([
            'photoProfile',
            // 'ktpPhoto',
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'village:id,name',
            'skills:id,title',
        ]);

        $user->loadCount([
            'posts',
            'completedRequestPosts as requested_count',
            'helpedTransactions as helped_count',
        ]);



        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getUserByFirstName(string $name)
    {
        $user = User::where('first_name', $name)->first();

        if (! $user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getUserByLastName(string $name)
    {
        $user = User::where('last_name', $name)->first();

        if (! $user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getProfile(User $user)
    {
        $user->load([
            'photoProfile',
            // 'ktpPhoto',
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'village:id,name',
            'skills:id,title',
        ]);

        $user->loadCount([
            'posts',
            'completedRequestPosts as requested_count',
            'helpedTransactions as helped_count',
        ]);



        return $this->successPayload($user, 'profile retrieved successfully');
    }

    public function getActivityAnalytics(string $userId)
    {
        try {
            $totalEarnings = (float) \App\Models\Transaction::where('helper_id', $userId)
                ->where('status', 'completed')
                ->sum('final_price');

            $totalSpending = (float) \App\Models\Transaction::where('requester_id', $userId)
                ->where('status', 'completed')
                ->sum('total_price');

            $activeEscrowBalance = (float) \App\Models\EscrowTransaction::where('status', 'held')
                ->whereHas('transaction', function ($q) use ($userId) {
                    $q->where('requester_id', $userId)
                      ->orWhere('helper_id', $userId);
                })
                ->sum('held_amount');

            $completedServicesCount = \App\Models\Transaction::where('helper_id', $userId)
                ->where('status', 'completed')
                ->count();

            $netIncome = $totalEarnings - $totalSpending;
            $cashFlowStatus = $netIncome >= 0 ? 'Surplus' : 'Deficit';

            $currentYear = date('Y');

            $earningsByMonth = \App\Models\Transaction::selectRaw('MONTH(finished_at) as month, SUM(final_price) as total')
                ->where('helper_id', $userId)
                ->where('status', 'completed')
                ->whereYear('finished_at', $currentYear)
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $spendingByMonth = \App\Models\Transaction::selectRaw('MONTH(finished_at) as month, SUM(total_price) as total')
                ->where('requester_id', $userId)
                ->where('status', 'completed')
                ->whereYear('finished_at', $currentYear)
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $incomeTrend = [];
            $spendingOverview = [];

            for ($m = 1; $m <= 12; $m++) {
                $incomeTrend[] = [
                    'month' => $months[$m - 1],
                    'amount' => (float) ($earningsByMonth[$m] ?? 0)
                ];
                $spendingOverview[] = [
                    'month' => $months[$m - 1],
                    'amount' => (float) ($spendingByMonth[$m] ?? 0)
                ];
            }

            return $this->successPayload([
                'total_earnings' => $totalEarnings,
                'total_spending' => $totalSpending,
                'active_escrow_balance' => $activeEscrowBalance,
                'completed_services_count' => $completedServicesCount,
                'cash_flow' => [
                    'total_earnings' => $totalEarnings,
                    'total_spending' => $totalSpending,
                    'net_income' => $netIncome,
                    'status' => $cashFlowStatus,
                ],
                'income_trend' => $incomeTrend,
                'spending_overview' => $spendingOverview,
            ], 'Activity analytics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorPayload($e->getMessage(), [], 500);
        }
    }

    public function getUsersPosts(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $type = $request->query('type');

        $query = $user->posts()->with([
            'category',
            'images',
        ]);

        if ($type === null) {
            $query->with([
                'requestDetail' => function ($q) {
                    $q->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                },
                'requestDetail.province:id,name',
                'requestDetail.city:id,name',
                'requestDetail.district:id,name',
                'requestDetail.village:id,name',
                'offerDetail' => function ($q) {
                    $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                },
                'offerDetail.province:id,name',
                'offerDetail.city:id,name',
                'offerDetail.district:id,name',
                'offerDetail.village:id,name',
            ]);
        }

        if ($type === 'request') {
            $query->where('type', 'request')
                ->with([
                    'requestDetail' => function ($q) {
                        $q->selectRaw('post_id, min_price, max_price, deadline, method_service, province_id, city_id, district_id, village_id, status, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                    },
                    'requestDetail.province:id,name',
                    'requestDetail.city:id,name',
                    'requestDetail.district:id,name',
                    'requestDetail.village:id,name',
                ]);
        } elseif ($type === 'offer') {
            $query->where('type', 'offer')
                ->with([
                    'offerDetail' => function ($q) {
                        $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, province_id, city_id, district_id, village_id, status, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                    },
                    'offerDetail.province:id,name',
                    'offerDetail.city:id,name',
                    'offerDetail.district:id,name',
                    'offerDetail.village:id,name',
                ]);
        }

        $posts = $query->get();

        $user->load([
            'photoProfile',
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'village:id,name',
            'skills:id,title',
        ]);

        return $this->successPayload([
            'users' => $user,
            'posts' => $posts,
        ], 'user posts retrieved successfully');
    }

    // TODO fitur untuk menampilkan history user
    public function getUsersHistory() {}

    // TODO update skill belum
    public function updateUser(string $id, array $data, array $profileImages, array $ktpImages)
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($id, $data, $profileImages, $ktpImages, &$uploadedPaths) {
                $user = User::findOrFail($id);

                // $currentProfileImage = $user->photoProfile;
                // $currentKtpImage = $user->ktpPhoto;

                $uploadedPaths = array_merge($uploadedPaths, $this->uploadProfileImage($profileImages, $user));
                $uploadedPaths = array_merge($uploadedPaths, $this->uploadKtpImage($ktpImages, $user));
                $user->update($data);

                // $this->deleteStoredImage($currentProfileImage);
                // $this->deleteStoredImage($currentKtpImage);

                $user->refresh();
                $user->load([
                    'photoProfile',
                    'ktpPhoto',
                    'province:id,name',
                    'city:id,name',
                    'district:id,name',
                    'village:id,name',
                    'skills:id,title',
                ]);

                return $this->successPayload($user, 'user updated successfully');
            });
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('user not found', [], 404);
        } catch (Exception $e) {
            $this->deleteStoredFiles($uploadedPaths);

            return $this->errorPayload($e->getMessage(), [$e->getFile().':'.$e->getLine().': '.$e->getTraceAsString()], 500);
        }
    }

    public function deleteUser(User $user)
    {
        $user->delete();

        return $this->successPayload([], 'user deleted successfully');
    }

    public function updateWalletBalance(string $userId, ?float $amount, ?float $walletBalance)
    {
        try {
            $user = User::findOrFail($userId);

            if ($walletBalance !== null) {
                $user->wallet_balance = $walletBalance;
            } elseif ($amount !== null) {
                $user->wallet_balance += $amount;
            }

            $user->save();

            return $this->successPayload([
                'wallet_balance' => (float) $user->wallet_balance
            ], 'Wallet balance updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('user not found', [], 404);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [], 500);
        }
    }

    public function changePassword(string $userId, string $newPassword)
    {
        try {
            $user = User::findOrFail($userId);
            $user->password = bcrypt($newPassword);
            $user->save();

            return $this->successPayload([], 'Password changed successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('user not found', [], 404);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [], 500);
        }
    }

    private function uploadProfileImage(array $uploadedImages, User $user)
    {
        $storedPaths = [];

        // Delete old profile image first
        if ($user->photoProfile) {
            Storage::disk('public')->delete($user->photoProfile->url);
            $user->photoProfile()->delete();
        }

        foreach ($uploadedImages as $imageFile) {
            $path = $imageFile->store('users-profile', 'public');

            $storedPaths[] = $path;

            $user->photoProfile()->create([
                'url' => $path,
                'type' => 'profile',
                'file_name' => $imageFile->getClientOriginalName(),
                'file_type' => $imageFile->getClientMimeType(),
            ]);
        }

        // VERY IMPORTANT
        // $user->unsetRelation('photoProfile');
        // $user->load('photoProfile');

        return $storedPaths;
    }

    private function uploadKtpImage(array $uploadedImages, User $user)
    {
        $storedPaths = [];

        // Delete old profile image first
        if ($user->ktpPhoto) {
            Storage::disk('public')->delete($user->ktpPhoto->url);
            $user->ktpPhoto()->delete();
        }

        foreach ($uploadedImages as $imageFile) {
            $path = $imageFile->store('users-ktp', 'public');

            $storedPaths[] = $path;

            $user->ktpPhoto()->create([
                'url' => $path,
                'type' => 'ktp',
                'file_name' => $imageFile->getClientOriginalName(),
                'file_type' => $imageFile->getClientMimeType(),
            ]);
        }
        $user->unsetRelation('ktpPhoto');
        $user->load('ktpPhoto');

        return $storedPaths;
    }

    private function deleteStoredImage($image): void
    {
        if (! $image) {
            return;
        }

        $image->delete();

        if (! Storage::disk('public')->delete($image->url)) {
            throw new Exception('failed to delete stored image');
        }
    }

    private function deleteStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }

            Storage::disk('public')->delete($path);
        }
    }
}
