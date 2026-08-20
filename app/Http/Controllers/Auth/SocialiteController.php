<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect(string $provider)
    {
        if ($provider === 'github') {
            return Socialite::driver($provider)
                ->scopes(['repo', 'read:org', 'user:email'])
                ->redirect();
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['oauth' => 'Authentication failed. Please try again.']);
        }

        $user = User::where('github_id', $socialUser->id)->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        if (! $user) {
            $user = User::create([
                'github_id' => $socialUser->id,
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'GitHub User',
                'email' => $socialUser->getEmail() ?? ($socialUser->id.'@github.oauth'),
                'email_verified_at' => now(),
                'github_token' => $socialUser->token,
                'github_refresh_token' => $socialUser->refreshToken,
                'password' => Hash::make(Str::random(32)),
                'password_set_at' => null,
            ]);
        } else {
            $user->update([
                'github_id' => $socialUser->id,
                'github_token' => $socialUser->token,
                'github_refresh_token' => $socialUser->refreshToken,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
