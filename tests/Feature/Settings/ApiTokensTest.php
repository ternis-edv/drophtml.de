<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('api tokens page is accessible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('api-tokens.edit'))
        ->assertOk();
});

test('can create an api token', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.api-tokens')
        ->set('createTokenName', 'Test Token')
        ->call('createToken')
        ->assertSet('createTokenName', '');

    expect($user->tokens)->toHaveCount(1);
    expect($user->tokens->first()->name)->toBe('Test Token');
});
