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
        $response->assertSee('Live Interactive Showcase');
        $response->assertSee('New: Static Media Cloud Hosting');
        $response->assertSee('Heavy Assets Don\'t Belong in Your Zip Bundles', false);
        $response->assertSee('Engineered for Modern Web Workflows');
        $response->assertSee('Simple, Honest Pricing');
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('Ready to Ship Your Next Static Site?');
        $response->assertSee('Deploy Free');
        $response->assertDontSee('@fluxStyles');
        $response->assertDontSee('copySnippet() { navigator.clipboard.writeText');
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
