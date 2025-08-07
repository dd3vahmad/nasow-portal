<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
            if (!$user) {
                return ApiResponse::error('User with this email does not exist.', 404);
            }

            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
                return ApiResponse::error('Verify your email before continuing. Check your mail for details', 401);
            }
            $cred = $user->credentials;

            if (!$user || !$cred || !Hash::check($validated['password'], $cred->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = (string) $user->createToken('x-auth-token', ['*'])->plainTextToken;
            $user->update(['last_login' => now()]);
            $user->settings()->firstOrCreate();

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
     * Sends password reset link to user's email
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiResponse
     */
    public function sendPasswordResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return ApiResponse::error('A user with this email was not found', 404);
            }

            // Generate and send the password reset token
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);

            return ApiResponse::success('Password reset link has been sent to your email');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * Handle the password reset request from the frontend.
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Responses\ApiResponse
     */
    public function reset(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:8|confirmed',
            ]);

            $user = User::where('email', $request->email)->first();

            // Verify the token and update the password
            $status = Password::broker()->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    // Update the password in the UserCredential model
                    $user->credentials()->update([
                        'password' => Hash::make($password), // Hash::make is redundant here due to mutator
                    ]);

                    // Fire the PasswordReset event
                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return ApiResponse::success('Password has been reset successfully');
            }

            return ApiResponse::error('Invalid token or email', 400);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
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
