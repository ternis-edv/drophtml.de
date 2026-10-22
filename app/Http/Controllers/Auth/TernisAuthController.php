<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\TernisOAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Login method "Ternis" — single sign-on against the Ternis Auth &
 * SSO Platform. Login methods on this platform are GitHub, Ternis
 * (SSO) and anonymous (public/restricted access only).
 */
class TernisAuthController extends Controller
{
    public function __construct(
        protected TernisOAuthService $oauth,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Start the authorization flow: redirect to the Ternis authorize
     * endpoint with PKCE and state.
     */
    public function redirect()
    {
        if (! $this->oauth->isConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Ternis SSO is not configured for this application.']);
        }

        return redirect()->away($this->oauth->authorizationUrl());
    }

    /**
     * Handle the authorization callback: validate state, exchange the
     * code, fetch identity claims, and log the user in.
     */
    public function callback()
    {
        if (request()->filled('error')) {
            return $this->fail('Authorization was cancelled on the Ternis platform.');
        }

        $state = request()->query('state');
        $code = request()->query('code');

        if (! $this->oauth->validateState($state)) {
            return $this->fail('Your Ternis sign-in session expired. Please try again.');
        }

        if (! is_string($code) || $code === '') {
            return $this->fail('Ternis did not return an authorization code.');
        }

        try {
            $tokens = $this->oauth->exchangeCode($code);
            $claims = $this->oauth->userinfo($tokens['access_token']);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Ternis sign-in could not be completed. Please try again.');
        }

        $user = $this->resolveUser($claims, $tokens);

        Auth::login($user, remember: true);

        $isNew = $user->wasRecentlyCreated;

        $this->activityLogService->log(
            $user,
            null,
            $isNew ? 'user_registered_ternis' : 'user_login_ternis',
            $isNew ? 'Authenticated via Ternis SSO (account created)' : 'Authenticated via Ternis SSO'
        );

        return redirect()->route('dashboard');
    }

    /**
     * Match the Ternis identity to a local user, creating or linking
     * as required.
     */
    protected function resolveUser(array $claims, array $tokens): User
    {
        $ternisId = (string) $claims['sub'];
        $email = (string) ($claims['email'] ?? '');
        $accessToken = (string) ($tokens['access_token'] ?? '');
        $refreshToken = isset($tokens['refresh_token']) ? (string) $tokens['refresh_token'] : null;

        $user = User::where('ternis_id', $ternisId)->first();

        if ($user) {
            $user->update([
                'ternis_token' => $accessToken,
                'ternis_refresh_token' => $refreshToken,
                'name' => $user->name ?: (string) ($claims['name'] ?? $user->name),
                'email_verified_at' => $user->email_verified_at ?? (($claims['email_verified'] ?? false) ? now() : null),
            ]);

            return $user;
        }

        // Link by verified email address; guard against a different
        // Ternis identity claiming an already-linked account.
        if ($email !== '') {
            $user = User::where('email', $email)->first();

            if ($user && $user->ternis_id && $user->ternis_id !== $ternisId) {
                abort(403, 'This email is linked to a different Ternis account.');
            }

            if ($user) {
                $user->update([
                    'ternis_id' => $ternisId,
                    'ternis_token' => $accessToken,
                    'ternis_refresh_token' => $refreshToken,
                    'email_verified_at' => $user->email_verified_at ?: (($claims['email_verified'] ?? false) ? now() : $user->email_verified_at),
                ]);

                return $user;
            }
        }

        $emailToUse = $email !== '' ? $email : $ternisId.'@ternis.sso';

        return User::create([
            'ternis_id' => $ternisId,
            'ternis_token' => $accessToken,
            'ternis_refresh_token' => $refreshToken,
            'name' => (string) ($claims['name'] ?? ($claims['preferred_username'] ?? 'Ternis User')),
            'email' => $emailToUse,
            'email_verified_at' => ($claims['email_verified'] ?? false) ? now() : null,
            'password' => Hash::make(Str::random(32)),
            'password_set_at' => null,
        ]);
    }

    protected function fail(string $message)
    {
        return redirect()
            ->route('login')
            ->withErrors(['oauth' => $message]);
    }
}
