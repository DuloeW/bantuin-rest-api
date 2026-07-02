<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Service\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

//TODO menentukan flow template response
class UserController extends Controller
{
    protected  UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
            'skills' => 'sometimes|required|array',
            'skills.*' => 'sometimes|required|string|exists:skills,id',
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
}