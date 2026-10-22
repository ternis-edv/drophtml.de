<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_all_reworked_sections_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Ship HTML in seconds.');
        $response->assertSee('Drop a site. Make it live.');
        $response->assertSee('Everything you need to ship static');
        $response->assertSee('Good to know');
        $response->assertSee('Made something? Make it live.');
        $response->assertSee('Deploy Free');
        $response->assertSee('Guest sites stay live for 24 hours.');
        $response->assertSee('Choose an HTML or ZIP file to deploy');
        $response->assertSee('Skip to content');
        $response->assertDontSee('@fluxStyles');

    }

    public function test_landing_page_shows_dashboard_link_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Go to Your Dashboard');
    }
}
