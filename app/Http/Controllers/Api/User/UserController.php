<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Service\Post\PostService;
use App\Service\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected UserService $userService;
    protected PostService $postService;

    public function __construct(UserService $userService, PostService $postService)
    {
        $this->userService = $userService;
        $this->postService = $postService;
    }

    public function getAll()
    {
        $result = $this->userService->getAllUsers();
        return response()->json($result, $result['code']);
    }

    public function getById(string $id)
    {
        $result = $this->userService->getUserById($id);

        return response()->json($result, $result['code']);
    }

    public function getByFirstName(string $name)
    {
        $result = $this->userService->getUserByFirstName($name);

        return response()->json($result, $result['code']);
    }

    public function getByLastName(string $name)
    {
        $result = $this->userService->getUserByLastName($name);

        return response()->json($result, $result['code']);
    }

    public function getActivityAnalytics(Request $request)
    {
        $userId = $request->user()->id;
        $result = $this->userService->getActivityAnalytics($userId);

        return response()->json($result, $result['code']);
    }

    public function getMyJobs(Request $request)
    {
        $userId = $request->user()->id;
        $type = $request->query('type');

        $result = $this->postService->getMyJobs($userId, $type);

        return response()->json($result, $result['code']);
    }

    public function getProfile(Request $request)
    {
        $loggedInUser = $request->user();
        $user = $this->userService->getProfile($loggedInUser);

        return response()->json($user, $user['code']);
    }

    public function update(Request $request, array $profileImages = [], array $ktpImages = [])
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'photo_profile' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ktp_photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
            'phone' => 'sometimes|required|string|max:20',
            'province_id' => 'sometimes|required|numeric',
            'district_id' => 'sometimes|required|numeric',
            'city_id' => 'sometimes|required|numeric',
            'village_id' => 'sometimes|required|numeric',   
            'neighborhood_unit' => 'sometimes|required|string|max:255',
        ]);

        if ($request->hasFile('photo_profile')) {
            $profileImages[] = $request->file('photo_profile');
        }

        if ($request->hasFile('ktp_photo')) {
            $ktpImages[] = $request->file('ktp_photo');
        }

        $result = $this->userService->updateUser($userId, $data, $profileImages, $ktpImages);

        return response()->json($result, $result['code']);
    }

    public function getUsersPosts(Request $request, string $id)
    {
        $result = $this->userService->getUsersPosts($request, $id);

        return response()->json($result, $result['code']);
    }

    public function updateWalletBalance(Request $request)
    {
        $request->validate([
            'amount' => 'sometimes|numeric',
            'wallet_balance' => 'sometimes|numeric|min:0',
        ]);

        $userId = $request->user()->id;
        $amount = $request->input('amount');
        $walletBalance = $request->input('wallet_balance');

        if ($amount === null && $walletBalance === null) {
            return response()->json([
                'code' => 400,
                'message' => 'Harap masukkan amount atau wallet_balance.',
                'data' => null
            ], 400);
        }

        $result = $this->userService->updateWalletBalance($userId, $amount, $walletBalance);

        return response()->json($result, $result['code']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->userService->changePassword($request->user()->id, $data['new_password']);

        return response()->json($result, $result['code']);
    }

    public function reportUser(Request $request, string $id)
    {
        $request->validate([
            'reason_category' => 'required|string',
            'description' => 'nullable|string',
            'report_images' => 'sometimes|array',
            'report_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['reason_category', 'description']);
        $reportImages = $request->file('report_images') ?? [];

        $userId = auth('sanctum')->user()->id;

        $result = $this->userService->reportUser($id, $userId, $data, $reportImages);

        return response()->json($result, $result['code']);
    }

    public function acceptTerms(Request $request)
    {
        $userId = $request->user()->id;
        $result = $this->userService->acceptTerms($userId);

        return response()->json($result, $result['code']);
    }

    public function hasBankAccount(string $userId)
    {
        $result = $this->userService->hasBankAccount($userId);

        return response()->json($result, $result['code']);
    }
}