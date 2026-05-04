<?php
namespace App\Service\User;

use App\Models\User;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService
{
    use ServiceResponse;

    public function getAllUsers()
    {
        $users = User::all();

        return $this->successPayload($users, 'users retrieved successfully');
    }

    public function getUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getUserByFirstName($name)
    {
        $user = User::where('first_name', $name)->first();

        if (!$user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

    public function getUserByLastName($name)
    {
        $user = User::where('last_name', $name)->first();

        if (!$user) {
            return $this->errorPayload('user not found', [], 404);
        }

        return $this->successPayload($user, 'user retrieved successfully');
    }

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
}