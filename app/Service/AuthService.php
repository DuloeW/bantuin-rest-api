<?php
namespace App\Service;

use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

//TODO masih error di test postman, login dan register
class AuthService
{
    use ServiceResponse;

    public function login(array $credentials)
    {

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->errorPayload('email or password is incorrect');
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successPayload([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'login successful');
    }

    public function register(array $data)
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successPayload([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'registration successful', 201);
    }
}