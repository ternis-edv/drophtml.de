<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives existing github users an avatar without another login', function () {
    $user = User::factory()->create(['github_id' => '12345']);
    expect($user->avatarUrl())->toBe('https://avatars.githubusercontent.com/u/12345?s=128&v=4');
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSee('https://avatars.githubusercontent.com/u/12345?s=128&amp;v=4', false)
        ->assertSee('referrerpolicy="no-referrer"', false);
});

it('stores a provider photo and supports a future ternis provider through the same interface', function () {
    $user = User::factory()->create();
    $user->setProviderAvatar('github', 'https://avatars.githubusercontent.com/u/99?v=4');
    expect($user->fresh()->avatar_provider)->toBe('github')->and($user->fresh()->avatarUrl())->toBe('https://avatars.githubusercontent.com/u/99?v=4');
    $user->setProviderAvatar('ternis', 'https://auth.example.test/picture/avatar.png');
    expect($user->fresh()->avatar_provider)->toBe('ternis')->and($user->fresh()->avatarUrl())->toBe('https://auth.example.test/picture/avatar.png');
});

it('rejects unsafe provider pictures and preserves initials for users without a photo', function ($url) {
    $user = User::factory()->create(['name' => 'Alex Example']);
    $user->setProviderAvatar('github', $url);
    expect($user->fresh()->avatarUrl())->toBeNull();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('AE');
})->with(['javascript:alert(1)', 'data:image/svg+xml,unsafe', 'http://avatars.githubusercontent.com/u/1', 'https://untrusted.example.test/photo', 'https://user:password@github.com/photo']);

it('does not allow profile requests to mass assign a provider photo', function () {
    $user = User::factory()->create();
    $user->fill(['avatar_url' => 'https://example.test/image', 'avatar_provider' => 'ternis'])->save();
    expect($user->fresh()->avatarUrl())->toBeNull();
});
