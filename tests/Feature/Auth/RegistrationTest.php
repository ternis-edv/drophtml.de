<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('register-user-button', false);
        $response->assertSee('Sign up with GitHub');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_email_form_is_hidden_when_email_signups_are_disabled(): void
    {
        config()->set('fortify.email_signups_enabled', false);

        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Sign up with GitHub');
        $response->assertSee('Email sign-ups are temporarily disabled');
        $response->assertDontSee('register-user-button', false);
        $response->assertDontSee('Or with email');
    }

    public function test_email_registration_is_rejected_when_email_signups_are_disabled(): void
    {
        config()->set('fortify.email_signups_enabled', false);

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
