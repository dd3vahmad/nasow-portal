<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->email))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success('Email already verified.');
        }

        // Mark email as verified in UserCredential
        $user->markEmailAsVerified();

        // Update user registration status
        $user->update(['reg_status' => 'personal-info']);

        return ApiResponse::success('Email verified successfully.');
    }
}
