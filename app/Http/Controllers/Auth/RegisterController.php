<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Register\RegisterAdminRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /**
     * Handle a admin registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerAdmin(RegisterAdminRequest $request)
    {
        try {
            $validated = $request->validated();
            $avatar = $request->avatar;

            $role = match ($validated['as']) {
                'national' => RoleType::NationalAdmin->value,
                'state' => RoleType::StateAdmin->value,
                default => RoleType::SupportStaff->value,
            };

            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'reg_status' => 'done',
            ]);

            if ($avatar) {
                $result = Cloudinary::uploadApi()->upload($avatar->getRealPath(), [
                    'folder' => 'users_avatars/',
                    'resource_type' => 'auto',
                ]);
                $secureUrl = $result;
                $user->update([ 'avatar_url' => $secureUrl ]);
            }

            $user->credentials()->create([
                'email' => $user->email,
                'password' => $validated['password'],
                'email_verified_at' => now(),
            ]);

            $role = Role::firstOrCreate(
                ['name' => $role],
                ['guard_name' => 'api']
            );
            $user->assignRole($role);

            $user->details()->create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'other_name' => $validated['other_name'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'state' => $validated['state'],
            ]);

            return ApiResponse::success('Admin registered successfully', $user);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage());
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = $this->validator($request->all());

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', 422, $validator->errors());
            }

            $user = $this->create($request->all());

            return $this->registered($request, $user);
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'reg_status' => 'verify-email',
            ]);

            $role = Role::firstOrCreate(
                ['name' => 'guest'],
                ['guard_name' => 'api']
            );
            $user->assignRole($role);

            $user->credentials()->create([
                'password' => $data['password'],
                'email' => $data['email']
            ]);

            return $user;
        });
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function registered(Request $request, User $user)
    {
        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
            return ApiResponse::success('Registration successful. Please verify your email.');
        }

        return ApiResponse::success('Registration successful.', $user);
    }
}
