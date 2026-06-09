<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Service\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

//TODO menentukan flow template response
class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getAll()
    {
        $result = $this->userService->getAllUsers();
        return response()->json($result, $result['code']);
    }

    public function getById($id)
    {
        $result = $this->userService->getUserById($id);

        return response()->json($result, $result['code']);
    }

    public function getByFirstName($name)
    {
        $result = $this->userService->getUserByFirstName($name);

        return response()->json($result, $result['code']);
    }

    public function getByLastName($name)
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

    public function update(Request $request)
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
            'phone' => 'sometimes|required|string|max:20',
            'province' => 'sometimes|required|string|max:255',
            'district' => 'sometimes|required|string|max:255',
            'sub_district' => 'sometimes|required|string|max:255',
            'village' => 'sometimes|required|string|max:255',
            'neighborhood_unit' => 'sometimes|required|string|max:255',
        ]);

        $result = $this->userService->updateUser($userId, $data);

        return response()->json($result, $result['code']);
    }
}