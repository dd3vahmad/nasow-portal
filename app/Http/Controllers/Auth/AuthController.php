<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* Register (email + password) */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'reg_status'  => 'local',
        ]);

        $user->credentials()->create([
            'password' => $data['password'],
        ]);

        // send verification email
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registered. Please verify your email.',
        ], 201);
    }

    /* Login (email + password) */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        /** password check */
        if (! $user || ! password_verify($data['password'], $user->credentials->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return ['token' => $token];
    }

    /* Logout */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
