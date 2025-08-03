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
            // Redirect to frontend with error
            $frontendUrl = config('app.frontend_url', 'https://nasow-portal.vercel.app');
            return redirect($frontendUrl . '/email/verify?id=' . $id . '&hash=' . $hash . '&error=invalid');
        }

        if ($user->hasVerifiedEmail()) {
            // Redirect to frontend with already verified status
            $frontendUrl = config('app.frontend_url', 'https://nasow-portal.vercel.app');
            return redirect($frontendUrl . '/email/verify?id=' . $id . '&hash=' . $hash . '&status=already-verified');
        }

        // Mark email as verified in UserCredential
        $user->markEmailAsVerified();

        // Update user registration status
        $user->update(['reg_status' => 'personal-info']);

        // Redirect to frontend with success
        $frontendUrl = config('app.frontend_url', 'https://nasow-portal.vercel.app');
        return redirect($frontendUrl . '/email/verify?id=' . $id . '&hash=' . $hash . '&status=success');
    }
}
