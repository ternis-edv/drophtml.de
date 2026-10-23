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

    public function test_landing_page_renders_seo_and_social_meta(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('og:title', false);
        $response->assertSee('og:description', false);
        $response->assertSee('og:image', false);
        $response->assertSee('og:image:width" content="1200"', false);
        $response->assertSee('twitter:card', false);
        $response->assertSee('summary_large_image', false);
        $response->assertSee(url('/og.png'), false);
    }

    public function test_landing_page_renders_structured_data(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "WebApplication"', false);
        $response->assertSee('"@type": "FAQPage"', false);
    }

    public function test_sitemap_lists_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        foreach ([route('home'), route('legal.show', ['page' => 'privacy']), route('legal.show', ['page' => 'terms']), route('legal.show', ['page' => 'imprint']), url('/docs/api')] as $url) {
            $response->assertSee('<loc>'.$url.'</loc>', false);
        }
    }
}
