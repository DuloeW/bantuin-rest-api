<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Service\User\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getAll()
    {
        return response()->json($this->userService->getAllUsers());
    }

    public function getById($id)
    {
        return response()->json($this->userService->getUserById($id));
    }

    public function getByFirstName($name)
    {
        return response()->json($this->userService->getUserByFirstName($name));
    }

    public function getByLastName($name)
    {
        return response()->json($this->userService->getUserByLastName($name));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->only(['first_name', 'last_name', 'email', 'password']);
        return response()->json($this->userService->updateUser($user, $data));
    }
}