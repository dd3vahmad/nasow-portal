<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\UserAuthProvider;

class OAuthController extends Controller
{
    public function redirect(string $provider)
    {
        return  Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $social = Socialite::driver($provider)->user();

        /* find or create user */
        $user = UserAuthProvider::where([
            'provider'          => strtoupper($provider),
            'provider_user_id'  => $social->getId(),
        ])->first()?->user;

        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => $social->getEmail()],
                ['name' => $social->getName(), 'reg_status' => 'oauth']
            );
        }

        /* store / update provider record */
        $user->providers()->updateOrCreate(
            ['provider' => strtoupper($provider)],
            [
                'provider_user_id' => $social->getId(),
                'access_token'     => $social->token,
                'refresh_token'    => $social->refreshToken,
            ]
        );

        /* email is auto‑verified when provider returns it */
        if (! $user->hasVerifiedEmail() && $social->getEmail()) {
            $user->markEmailAsVerified();
        }

        /* issue token and redirect / respond */
        $token = $user->createToken($provider)->plainTextToken;

        return redirect(config('app.frontend_url') . '/oauth?token=' . $token);
    }
}
