<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
                ->scopes(['repo', 'read:org'])
                ->redirect();
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['oauth' => 'Authentication failed.']);
        }

        $user = User::updateOrCreate([
            'github_id' => $socialUser->id,
        ], [
            'name' => $socialUser->name ?? $socialUser->nickname,
            'email' => $socialUser->email,
            'github_token' => $socialUser->token,
            'github_refresh_token' => $socialUser->refreshToken,
            // If it's a new user, we might need to set a dummy password
            'password' => Hash::make(Str::random(24)),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
