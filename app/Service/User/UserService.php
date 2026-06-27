<?php

namespace App\Service\User;

use App\Models\User;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

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

        if (!$user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getUserByFirstName(string $name)
    {
        $user = User::where('first_name', $name)->first();

        if (!$user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getUserByLastName(string $name)
    {
        $user = User::where('last_name', $name)->first();

        if (!$user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getProfile(User $user)
    {
        return $this->successPayload($user, 'profile retrieved successfully');
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
                    $q->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
                },
                'requestDetail.province:id,name',
                'requestDetail.city:id,name',
                'requestDetail.district:id,name',
                'requestDetail.village:id,name',
                'offerDetail' => function ($q) {
                    $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
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
                        $q->selectRaw('post_id, min_price, max_price, deadline, method_service, province_id, city_id, district_id, village_id, status, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
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
                        $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, province_id, city_id, district_id, village_id, status, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
                    },
                    'offerDetail.province:id,name',
                    'offerDetail.city:id,name',
                    'offerDetail.district:id,name',
                    'offerDetail.village:id,name',
                ]);
        }

        $posts = $query->get();

        return $this->successPayload($posts, 'user posts retrieved successfully');
    }

    // TODO fitur untuk menampilkan history user
    public function getUsersHistory() {}

    public function updateUser(string $id, array $data)
    {
        try {
            $user = User::findOrFail($id);
            $user->update($data);
            return $this->successPayload($user, 'user updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('user not found', [], 404);
        } catch (Exception $e) {
            return $this->errorPayload('an error occurred while updating the user', [], 500);
        }
    }

    public function deleteUser(User $user)
    {
        $user->delete();

        return $this->successPayload([], 'user deleted successfully');
    }

    private function uploadImages(array $uploadedImages, User $user)
    {
        foreach ($uploadedImages as $imageFile) {
            $path = $imageFile->store('users', 'public');

            $user->photoProfile()->create([
                'url' => $path,
                'file_name' => $imageFile->getClientOriginalName(),
                'file_type' => $imageFile->getClientMimeType(),
            ]);
        }
    }
}
