<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    protected const SUPPORTED_PROVIDERS = ['github'];

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function redirect(string $provider)
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            abort(404, 'OAuth provider not supported.');
        }

        if ($provider === 'github') {
            return Socialite::driver($provider)
                ->scopes(['repo', 'read:org', 'user:email'])
                ->redirect();
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            abort(404, 'OAuth provider not supported.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['oauth' => 'Authentication failed. Please try again.']);
        }

        $user = User::where('github_id', $socialUser->id)->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        $isNew = false;
        if (! $user) {
            $isNew = true;
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

        $this->activityLogService->log(
            $user,
            null,
            $isNew ? 'user_registered_oauth' : 'user_login_oauth',
            "Authenticated via {$provider}"
        );

        return redirect()->route('dashboard');
    }
}
