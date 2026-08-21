<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('github redirect contains required scopes', function () {
    $response = $this->get(route('social.redirect', 'github'));

    $response->assertStatus(302);
    $target = $response->getTargetUrl();

    expect($target)->toContain('github.com/login/oauth/authorize');
    expect($target)->toContain('repo');
    expect($target)->toContain('read%3Aorg');
    expect($target)->toContain('user%3Aemail');
});

test('github user without public email is created with fallback email', function () {
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->id = '555444';
    $abstractUser->token = 'gh_no_email_token';
    $abstractUser->refreshToken = null;
    $abstractUser->shouldReceive('getId')->andReturn('555444');
    $abstractUser->shouldReceive('getName')->andReturn(null);
    $abstractUser->shouldReceive('getNickname')->andReturn('ghostdev');
    $abstractUser->shouldReceive('getEmail')->andReturn(null);

    $provider = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get(route('social.callback', 'github'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::where('github_id', '555444')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('ghostdev');
    expect($user->email)->toBe('555444@github.oauth');
    expect($user->hasVerifiedEmail())->toBeTrue();
    expect($user->password_set_at)->toBeNull();
});

test('subsequent github login updates token and refreshes user session', function () {
    $existingUser = User::create([
        'name' => 'Returning Dev',
        'email' => 'returning@example.com',
        'github_id' => '777888',
        'github_token' => 'old_token',
        'email_verified_at' => now(),
        'password' => 'secret',
        'password_set_at' => null,
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->id = '777888';
    $abstractUser->token = 'new_updated_token';
    $abstractUser->refreshToken = 'refresh_abc';
    $abstractUser->shouldReceive('getId')->andReturn('777888');
    $abstractUser->shouldReceive('getName')->andReturn('Returning Dev');
    $abstractUser->shouldReceive('getNickname')->andReturn('returningdev');
    $abstractUser->shouldReceive('getEmail')->andReturn('returning@example.com');

    $provider = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get(route('social.callback', 'github'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($existingUser);

    $existingUser->refresh();
    expect($existingUser->github_token)->toBe('new_updated_token');
    expect($existingUser->github_refresh_token)->toBe('refresh_abc');
});

test('oauth failure gracefully redirects to login with error', function () {
    $provider = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $provider->shouldReceive('user')->andThrow(new Exception('Access denied by user'));

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get(route('social.callback', 'github'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['oauth']);
});
