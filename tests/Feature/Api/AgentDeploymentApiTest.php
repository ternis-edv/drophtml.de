<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('media');
    Storage::fake('local');
});

test('agent can create a site via API using raw HTML string', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $html = '<!DOCTYPE html><html><head><title>Agent Test</title></head><body><h1>Deployed by AI</h1></body></html>';

    $response = $this->postJson('/api/sites', [
        'original_name' => 'Agent Test Site',
        'html' => $html,
        'is_permanent' => true,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.original_name', 'Agent Test Site');
    $response->assertJsonPath('data.is_permanent', true);

    $site = Site::where('original_name', 'Agent Test Site')->first();
    expect($site)->not->toBeNull();
    expect($site->user_id)->toBe($user->id);
    expect($site->is_permanent)->toBeTrue();

    // Verify index.html was written to disk
    expect(Storage::disk('public')->exists("{$site->path}/index.html"))->toBeTrue();
    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toBe($html);
});

test('agent can create a site via API using files array', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $files = [
        'index.html' => '<!DOCTYPE html><html><head><link rel="stylesheet" href="style.css"></head><body><h1>Hello Agent</h1></body></html>',
        'css/style.css' => 'body { color: red; }',
        'js/app.js' => 'console.log("agent ready");',
    ];

    $response = $this->postJson('/api/sites', [
        'original_name' => 'Multi File Site',
        'files' => $files,
        'is_permanent' => false,
    ]);

    $response->assertCreated();
    $site = Site::where('original_name', 'Multi File Site')->first();
    expect($site)->not->toBeNull();

    expect(Storage::disk('public')->exists("{$site->path}/index.html"))->toBeTrue();
    expect(Storage::disk('public')->exists("{$site->path}/css/style.css"))->toBeTrue();
    expect(Storage::disk('public')->exists("{$site->path}/js/app.js"))->toBeTrue();
    expect(Storage::disk('public')->get("{$site->path}/css/style.css"))->toBe('body { color: red; }');
});

test('agent cannot deploy files with dangerous extensions', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $files = [
        'index.html' => '<h1>Normal</h1>',
        'shell.php' => '<?php echo "evil"; ?>',
    ];

    $response = $this->postJson('/api/sites', [
        'original_name' => 'Dangerous Site',
        'files' => $files,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Uploading .php files is not permitted for security reasons.',
    ]);
});

test('agent cannot deploy files with path traversal', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $files = [
        '../secrets.txt' => 'attack',
    ];

    $response = $this->postJson('/api/sites', [
        'original_name' => 'Traversal Site',
        'files' => $files,
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('path traversal detected');
});

test('agent can redeploy an existing site using raw HTML', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'agent-site-update',
        'original_name' => 'Original Agent Site',
        'path' => 'sites/agent-site-update',
        'status' => 'active',
    ]);

    Storage::disk('public')->put('sites/agent-site-update/index.html', '<h1>Version 1</h1>');

    $updatedHtml = '<h1>Version 2 by AI Agent</h1>';

    $response = $this->postJson("/api/sites/{$site->id}/deploy", [
        'html' => $updatedHtml,
        'commit_message' => 'Agent updated header content',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.commit_message', 'Agent updated header content');

    expect(Storage::disk('public')->get('sites/agent-site-update/index.html'))->toBe($updatedHtml);
    expect($site->deployments()->count())->toBe(1);
});

test('agent can redeploy an existing site using files array', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'agent-redeploy-files',
        'original_name' => 'Agent Site Files',
        'path' => 'sites/agent-redeploy-files',
        'status' => 'active',
    ]);

    $response = $this->postJson("/api/sites/{$site->id}/deploy", [
        'files' => [
            'index.html' => '<h2>New Layout</h2>',
            'theme.css' => 'h2 { color: blue; }',
        ],
        'commit_message' => 'Agent re-deployed multi-file update',
    ]);

    $response->assertCreated();
    expect(Storage::disk('public')->get('sites/agent-redeploy-files/index.html'))->toBe('<h2>New Layout</h2>');
    expect(Storage::disk('public')->get('sites/agent-redeploy-files/theme.css'))->toBe('h2 { color: blue; }');
});

test('agent can upload media asset via base64 data URI to cloud storage', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // 1x1 transparent PNG data URI
    $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $response = $this->postJson('/api/media', [
        'name' => 'agent-chart.png',
        'data' => $base64Png,
        'storage_driver' => 'asset_storage',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.original_name', 'agent-chart.png');
    $response->assertJsonPath('data.storage_driver', 'asset_storage');
    $response->assertJsonPath('data.file_type', 'image');
    expect($response->json('data.url'))->not->toBeEmpty();
    expect($response->json('data.markdown_tag'))->toContain('![agent-chart.png]');

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $user->id,
        'original_name' => 'agent-chart.png',
        'storage_driver' => 'asset_storage',
    ]);
});

test('agent can upload media asset via base64 to site code storage', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'agent-media-site',
        'original_name' => 'Agent Media Site',
        'path' => 'sites/agent-media-site',
        'status' => 'active',
    ]);

    $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $response = $this->postJson('/api/media', [
        'name' => 'logo.png',
        'data' => $base64Png,
        'site_id' => $site->id,
        'storage_driver' => 'code',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.storage_driver', 'code');
    $response->assertJsonPath('data.site_id', $site->id);

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'logo.png',
        'storage_driver' => 'code',
    ]);
});

test('agent cannot upload dangerous extension via base64 media', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $base64Script = 'data:text/plain;base64,PD9waHAgZWNobyAnaGVsbG8nOyA/Pg==';

    $response = $this->postJson('/api/media', [
        'name' => 'backdoor.php',
        'data' => $base64Script,
        'storage_driver' => 'asset_storage',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Uploading .php files is not permitted for security reasons.',
    ]);
});

test('public skill specification endpoints and docs page are accessible', function () {
    $responseSkill = $this->get('/skills/drophtml/SKILL.md');
    $responseSkill->assertOk();
    $responseSkill->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    expect($responseSkill->getContent())->toContain('name: drophtml');
    expect($responseSkill->getContent())->toContain('# DropHTML for Agents');

    $responseWellKnown = $this->get('/.well-known/agent-skills/drophtml/SKILL.md');
    $responseWellKnown->assertOk();
    $responseWellKnown->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    expect($responseWellKnown->getContent())->toContain('name: drophtml');

    $responseDocs = $this->get('/docs/agents');
    $responseDocs->assertOk();
    $responseDocs->assertSee('DropHTML for AI Agents');
    $responseDocs->assertSee('SKILL.md Specification');
});

test('agent can create a site with custom slug via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/sites', [
        'original_name' => 'Custom Slug Site',
        'html' => '<h1>Custom Slug Works</h1>',
        'custom_slug' => 'my-agent-demo',
        'is_permanent' => true,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.slug', 'my-agent-demo');
    $this->assertDatabaseHas('sites', [
        'user_id' => $user->id,
        'slug' => 'my-agent-demo',
    ]);
});

test('landing page renders terminal section and agent links', function () {
    $response = $this->get('/');
    $response->assertOk();
    $response->assertSee('Deploy from Terminal or AI Agents');
    $response->assertSee('drophtml deploy');
    $response->assertSee('Agent Skill');
});

test('settings api tokens page renders agent quickstart guide', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/api-tokens');
    $response->assertOk();
    $response->assertSee('Use with AI Agents &amp; CLI', false);
    $response->assertSee('DROPHTML_API_TOKEN');
});
