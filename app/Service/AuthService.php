<?php
namespace App\Service;

use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    use ServiceResponse;

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorPayload('invalid credentials', [], 401);
        }

        $user = Auth::user();
        
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;
        $expiredInMinutes = config('sanctum.expiration');

        return $this->authSuccessPayload([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiredInMinutes / 60,
            'user' => $user,
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

        return $this->authSuccessPayload([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
            'user' => $user,
        ], 'registration successful', 201);
    }
}