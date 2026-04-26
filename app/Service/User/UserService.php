<?php
namespace App\Service\User;
use App\Models\User;

class UserService
{

    public function getAllUsers()
    {
        $users = User::all();
        return [
            'success' => true,
            'code' => 200,
            'message' => 'users retrieved successfully',
            'data' => $users,
        ];
    }

    public function getUserById($id)
    {
        $user = User::find($id);
        return [
            'success' => true,
            'code' => 200,
            'message' => 'user retrieved successfully',
            'data' => $user,
        ];
    }

    public function getUserByFirstName($name)
    {
        $user = User::findOrFail($name);
        return [
            'success' => true,
            'code' => 200,
            'message' => 'user retrieved successfully',
            'data' => $user,
        ];
    }

    public function getUserByLastName($name)
    {
        $user = User::where('last_name', $name)->first();
        return [
            'success' => true,
            'code' => 200,
            'message' => 'user retrieved successfully',
            'data' => $user,
        ];
    }

    public function updateUser(User $user, array $data)
    {
        $user->update($data);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'user updated successfully',
            'data' => $user,
        ];
    }

    public function deleteUser(User $user)
    {
        $user->delete();
        return [
            'success' => true,
            'code' => 200,
            'message' => 'user deleted successfully',
        ];
    }
}