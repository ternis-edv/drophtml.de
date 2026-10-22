<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * TernisOAuth — OAuth 2.0 / OpenID Connect client for the
 * Ternis Auth &amp; SSO Platform (auth.ternis.net).
 *
 * Implements the authorization-code grant with PKCE (S256) and state
 * protection, token exchange, and identity claim retrieval — all
 * endpoints per the Ternis REST API reference:
 *
 *   GET  {base}/.well-known/openid-configuration
 *   GET  {base}/oauth/authorize
 *   POST {base}/oauth/token
 *   GET  {base}/oauth/userinfo
 */
class TernisOAuthService
{
    /**
     * Build the authorization redirect URL and persist PKCE/state
     * parameters in the session.
     */
    public function authorizationUrl(): string
    {
        $this->assertConfigured();

        $state = Str::random(40);
        $verifier = Str::random(64);
        $challenge = $this->codeChallenge($verifier);

        session()->put('ternis_oauth_state', $state);
        session()->put('ternis_oauth_verifier', $verifier);

        return sprintf(
            '%s/oauth/authorize?%s',
            $this->baseUrl(),
            http_build_query([
                'client_id' => $this->clientId(),
                'redirect_uri' => $this->redirectUri(),
                'response_type' => 'code',
                'scope' => $this->scopes(),
                'state' => $state,
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ])
        );
    }

    /**
     * Validate the callback state parameter against the session value.
     */
    public function validateState(?string $state): bool
    {
        $expected = session()->pull('ternis_oauth_state');

        return is_string($state) && $expected !== null && hash_equals($expected, $state);
    }

    /**
     * Exchange an authorization code for an access token.
     *
     * @return array{access_token: string, refresh_token?: string, expires_in?: int, id_token?: string}
     *
     * @throws RuntimeException when the token endpoint rejects the request.
     */
    public function exchangeCode(string $code): array
    {
        $verifier = session()->pull('ternis_oauth_verifier');

        $response = Http::asForm()
            ->timeout(15)
            ->connectTimeout(5)
            ->acceptJson()
            ->post($this->baseUrl().'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'redirect_uri' => $this->redirectUri(),
                'code' => $code,
                'code_verifier' => $verifier,
            ]);

        if ($response->failed()) {
            $context = $verifier ? 'PKCE' : 'no code_verifier (legacy session)';

            throw new RuntimeException('Ternis token exchange failed ('.$context.'): '.$response->status());
        }

        return (array) $response->json();
    }

    /**
     * Retrieve the OpenID Connect identity claims for an access token.
     *
     * @return array{sub: string, name?: string, email?: string, email_verified?: bool, picture?: string, user_type?: string, is_admin?: bool}
     *
     * @throws RuntimeException when the userinfo endpoint rejects the request.
     */
    public function userinfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->connectTimeout(5)
            ->acceptJson()
            ->get($this->baseUrl().'/oauth/userinfo');

        if ($response->failed()) {
            throw new RuntimeException('Ternis userinfo request failed: '.$response->status());
        }

        $claims = (array) $response->json();

        if (empty($claims['sub'])) {
            throw new RuntimeException('Ternis userinfo response is missing the "sub" claim.');
        }

        return $claims;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId()) && ! empty($this->clientSecret());
    }

    protected function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('Ternis SSO is not configured (missing TERNIS_CLIENT_ID or TERNIS_CLIENT_SECRET).');
        }
    }

    /**
     * Derive the S256 PKCE code challenge from a code verifier.
     */
    protected function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.ternis.base_url'), '/') ?: 'https://auth.ternis.net';
    }

    protected function clientId(): string
    {
        return (string) config('services.ternis.client_id');
    }

    protected function clientSecret(): string
    {
        return (string) config('services.ternis.client_secret');
    }

    protected function scopes(): string
    {
        return (string) config('services.ternis.scopes', 'openid profile email ternis:sso');
    }

    protected function redirectUri(): string
    {
        return rtrim((string) url('/'), '/').(string) config('services.ternis.redirect', '/auth/ternis/callback');
    }
}
