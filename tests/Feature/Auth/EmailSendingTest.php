<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('verification email is sent upon user registration', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Jane Developer',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    $user = User::where('email', 'jane@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('user can request a new verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->post(route('verification.send'));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('password reset notification is sent with working reset URL', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'developer@drophtml.de',
    ]);

    $response = $this->post(route('password.email'), [
        'email' => 'developer@drophtml.de',
    ]);

    $response->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $mailData = $notification->toMail($user);
        expect($mailData->actionUrl)->toContain('/reset-password/'.$notification->token);
        expect($mailData->subject)->toContain('Reset');

        return true;
    });
});
