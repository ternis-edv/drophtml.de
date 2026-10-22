<?php

use App\Models\User;
use App\Services\TernisOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.ternis', [
        'base_url' => 'https://auth.ternis.net',
        'client_id' => 'ternis-client-uuid',
        'client_secret' => 'ternis-secret',
        'redirect' => '/auth/ternis/callback',
        'scopes' => 'openid profile email ternis:sso',
    ]);

    // Simulate session storage in tests
    session()->flush();
});

function fakeTernisEndpoints(): void
{
    Http::fake([
        'auth.ternis.net/oauth/token' => Http::response([
            'token_type' => 'Bearer',
            'expires_in' => 31536000,
            'access_token' => 'ternis_access_token',
            'refresh_token' => 'ternis_refresh_token',
        ], 200),
        'auth.ternis.net/oauth/userinfo' => Http::response([
            'sub' => 'b2f6b897-4001-460d-8386-db93f1d8c1c4',
            'name' => 'Elena Rostova',
            'preferred_username' => 'elena.rostova',
            'email' => 'elena@ternis.org',
            'email_verified' => true,
        ], 200),
    ]);
}

test('redirect builds authorize url with pkce and state', function () {
    $response = $this->get(route('social.redirect.ternis'));

    $response->assertRedirect();

    $url = $response->getTargetUrl();
    expect($url)->toContain('https://auth.ternis.net/oauth/authorize')
        ->toContain('client_id=ternis-client-uuid')
        ->toContain('response_type=code')
        ->toContain('code_challenge_method=S256')
        ->toContain('scope=openid+profile+email+ternis%3Asso');

    expect(session('ternis_oauth_state'))->not->toBeNull();
    expect(session('ternis_oauth_verifier'))->not->toBeNull();

    // S256 challenge round-trips with the stored verifier
    $verifier = session('ternis_oauth_verifier');
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    expect($url)->toContain('code_challenge='.rawurlencode($challenge));
});

test('redirect fails gracefully when not configured', function () {
    config()->set('services.ternis.client_id', null);
    config()->set('services.ternis.client_secret', null);

    $response = $this->get(route('social.redirect.ternis'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['oauth']);
});

test('user can log in via ternis oauth callback', function () {
    fakeTernisEndpoints();

    // Start the flow so the session holds a valid state + verifier
    $this->get(route('social.redirect.ternis'));
    $state = session('ternis_oauth_state');

    $response = $this->get(route('social.callback.ternis', ['code' => 'auth-code', 'state' => $state]));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    // Verify PKCE code_verifier was sent to the token endpoint
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'oauth/token')
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'auth-code'
            && ! empty($request['code_verifier']);
    });

    $user = User::where('email', 'elena@ternis.org')->first();
    expect($user)->not->toBeNull();
    expect($user->ternis_id)->toBe('b2f6b897-4001-460d-8386-db93f1d8c1c4');
    expect($user->ternis_token)->toBe('ternis_access_token');
    expect($user->ternis_refresh_token)->toBe('ternis_refresh_token');
    expect($user->email_verified_at)->not->toBeNull();
});

test('existing user with same email is linked to ternis identity', function () {
    fakeTernisEndpoints();

    User::factory()->create([
        'email' => 'elena@ternis.org',
        'name' => 'Local Elena',
    ]);

    $this->get(route('social.redirect.ternis'));
    $state = session('ternis_oauth_state');

    $this->get(route('social.callback.ternis', ['code' => 'auth-code', 'state' => $state]));

    expect(User::where('email', 'elena@ternis.org')->first()->ternis_id)
        ->toBe('b2f6b897-4001-460d-8386-db93f1d8c1c4');
});

test('different ternis identity cannot claim linked email', function () {
    fakeTernisEndpoints();

    User::factory()->create([
        'email' => 'elena@ternis.org',
        'ternis_id' => 'someone-else',
    ]);

    $this->get(route('social.redirect.ternis'));
    $state = session('ternis_oauth_state');

    $response = $this->get(route('social.callback.ternis', ['code' => 'auth-code', 'state' => $state]));

    $response->assertForbidden();
});

test('callback rejects invalid state', function () {
    $response = $this->get(route('social.callback.ternis', ['code' => 'auth-code', 'state' => 'forged-state']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['oauth']);
    $this->assertGuest();
});

test('callback rejects cancelled authorization', function () {
    $this->get(route('social.redirect.ternis'));

    $response = $this->get(route('social.callback.ternis', ['error' => 'access_denied']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['oauth']);
    $this->assertGuest();
});

test('ternis oauth service rejects userinfo without sub claim', function () {
    config()->set('services.ternis', [
        'base_url' => 'https://auth.ternis.net',
        'client_id' => 'c',
        'client_secret' => 's',
        'redirect' => '/auth/ternis/callback',
        'scopes' => 'openid',
    ]);

    $service = app(TernisOAuthService::class);

    Http::fake(['auth.ternis.net/oauth/userinfo' => Http::response(['name' => 'No Sub'], 200)]);

    expect(fn () => $service->userinfo('token'))
        ->toThrow(RuntimeException::class);
});
