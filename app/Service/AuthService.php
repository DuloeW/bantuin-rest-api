<?php
namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $credentials)
    {
        if(!Auth::attempt($credentials)) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'email or password is incorrect',
                'data' => [],
            ];
        }

        $user = User::where('email', $credentials['email'])->first();
    
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'code' => 200,
            'message' => 'login successful',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ];
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

        return [
            'success' => true,
            'code' => 201,
            'message' => 'registration successful',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ];
    }
}