<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller {
    /**
     * Handle a login request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = $this->validator($request->all());

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', 422, $validator->errors());
            }
            $validated = $validator->validated();
            $user = User::where('email', $validated['email'])->first();
            $cred = $user->credentials;

            if (!$user || !$cred || !Hash::check($validated['password'], $cred->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = (string) $user->createToken('x-auth-token', ['*'])->plainTextToken;
            $user->update(['last_login' => now()]);

            return ApiResponse::success('Login successful', [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('sanctum.expiration') * 60,
            ]);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return ApiResponse::success(__('auth.logged_out_successfully'));
    }
}
