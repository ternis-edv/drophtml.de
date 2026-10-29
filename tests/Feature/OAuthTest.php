<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('it redirects to github', function () {
    $response = $this->get(route('social.redirect', 'github'));

    $response->assertStatus(302);
    $this->assertStringContainsString('github.com/login/oauth/authorize', $response->getTargetUrl());
});

test('user can log in via github oauth callback', function () {
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->id = '123456';
    $abstractUser->token = 'gh_test_token';
    $abstractUser->refreshToken = 'gh_refresh_token';
    $abstractUser->shouldReceive('getId')->andReturn('123456');
    $abstractUser->shouldReceive('getName')->andReturn('Octocat Developer');
    $abstractUser->shouldReceive('getNickname')->andReturn('octocat');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://avatars.githubusercontent.com/u/123456?v=4');
    $abstractUser->shouldReceive('getEmail')->andReturn('octo@example.com');

    $provider = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get(route('social.callback', 'github'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::where('email', 'octo@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->github_id)->toBe('123456');
    expect($user->github_token)->toBe('gh_test_token');
    expect($user->avatar_provider)->toBe('github');
    expect($user->avatarUrl())->toBe('https://avatars.githubusercontent.com/u/123456?v=4');
});

test('existing user with email links github on oauth callback', function () {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->id = '999888';
    $abstractUser->token = 'gh_new_token';
    $abstractUser->refreshToken = 'gh_new_refresh';
    $abstractUser->shouldReceive('getId')->andReturn('999888');
    $abstractUser->shouldReceive('getName')->andReturn('Existing User');
    $abstractUser->shouldReceive('getNickname')->andReturn('existing');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://avatars.githubusercontent.com/u/123456?v=4');
    $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');

    $provider = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get(route('social.callback', 'github'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($existingUser);

    $existingUser->refresh();
    expect($existingUser->github_id)->toBe('999888');
    expect($existingUser->github_token)->toBe('gh_new_token');
    expect($existingUser->avatar_provider)->toBe('github');
});
