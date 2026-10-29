<?php

use App\Ai\Budget;
use App\Ai\HelpAgent;
use App\Ai\SiteHelpTool;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('enforces per-user minute and daily budgets without refunds', function () {
    $budget = new Budget;
    $budget->reserve('alice');
    try {
        $budget->reserve('alice');
        $this->fail('Expected limit');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(429);
    }
    expect(DB::table('assistant_budgets')->where('key', 'like', 'global:%')->value('used'))->toBe(1);
    for ($i = 1; $i < 10; $i++) {
        $this->travel(61)->seconds();
        $budget->reserve('alice');
    }
    $this->travel(61)->seconds();
    expect(fn () => $budget->reserve('alice'))->toThrow(HttpException::class);
});

it('isolates site data and requires opt-in for mutations', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $site = Site::create(['user_id' => $owner->id, 'slug' => 'private', 'original_name' => 'Secret', 'path' => 'test', 'entry_path' => 'index.html']);
    $request = new Request(['action' => 'rename', 'site_id' => $site->id, 'name' => 'Changed']);
    (new SiteHelpTool($other, true))->handle($request);
    expect($site->fresh()->original_name)->toBe('Secret');
    (new SiteHelpTool($owner))->handle($request);
    expect($site->fresh()->original_name)->toBe('Secret');
    (new SiteHelpTool($owner, true))->handle($request);
    expect($site->fresh()->original_name)->toBe('Changed');
    expect((new SiteHelpTool($other))->handle(new Request(['action' => 'list'])))->not->toContain('Secret', 'Changed');
    expect(DB::table('assistant_audits')->count())->toBe(4);
});

it('logs disabled and invalid requests without calling the provider', function () {
    $user = User::factory()->create();
    HelpAgent::fake()->preventStrayPrompts();
    $this->actingAs($user)->post(route('dashboard.help.ask'), ['message' => 'Help'])->assertStatus(503);
    config(['assistant.enabled' => true, 'ai.providers.xai.key' => 'fake']);
    $this->postJson(route('dashboard.help.ask'), ['message' => str_repeat('x', 2001)])->assertUnprocessable();
    expect(DB::table('assistant_audits')->count())->toBe(2);
    HelpAgent::assertNeverPrompted();
});

it('returns a bounded agent response and logs usage', function () {
    config(['assistant.enabled' => true, 'ai.providers.xai.key' => 'fake']);
    HelpAgent::fake(['Try the site settings page.']);
    $this->actingAs(User::factory()->create())
        ->post(route('dashboard.help.ask'), ['message' => 'Where are settings?'])
        ->assertRedirect()->assertSessionHas('assistant_answer', 'Try the site settings page.');
    expect(DB::table('assistant_audits')->where('kind', 'response')->value('status'))->toBe('success');
});

it('enforces the shared daily cap', function () {
    DB::table('assistant_budgets')->insert(['key' => 'global:'.now('UTC')->format('Y-m-d'), 'used' => 100]);
    expect(fn () => (new Budget)->reserve('new-user'))->toThrow(HttpException::class);
});

it('renders the help page with safe defaults and remaining allowance', function () {
    $user = User::factory()->create();
    (new Budget)->reserve($user->id);
    $this->actingAs($user)->get(route('dashboard.help'))
        ->assertOk()->assertViewHas('available', false)
        ->assertViewHas('usage', fn ($usage) => $usage['remaining'] === 9 && $usage['retry_at'] > now()->timestamp)
        ->assertSee('Allow site renaming for this request')->assertSee('Your question');
});

it('provides JSON answers and quota feedback without reloading the page', function () {
    config(['assistant.enabled' => true, 'ai.providers.xai.key' => 'fake']);
    HelpAgent::fake(['Use the settings page.']);
    $this->actingAs(User::factory()->create());
    $this->postJson(route('dashboard.help.ask'), ['message' => 'Help'])->assertOk()
        ->assertJsonPath('answer', 'Use the settings page.')->assertJsonPath('usage.remaining', 9);
    $this->postJson(route('dashboard.help.ask'), ['message' => 'Again'])->assertStatus(429)
        ->assertJsonPath('usage.remaining', 9);
    expect(DB::table('assistant_audits')->where('kind', 'request')->where('status', '429')->count())->toBe(1);
});

it('keeps the tool budget when tools are resolved repeatedly', function () {
    $agent = new HelpAgent(User::factory()->create());
    expect($agent->tools()[0])->toBe($agent->tools()[0]);
    $tool = $agent->tools()[0];
    for ($i = 0; $i < 3; $i++) {
        $tool->handle(new Request(['action' => 'list']));
    }
    expect($agent->tools()[0]->handle(new Request(['action' => 'list'])))->toContain('permission denied');
});
