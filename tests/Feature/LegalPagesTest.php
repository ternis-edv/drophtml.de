<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legal pages can be rendered and show partner credits', function () {
    $privacy = $this->get('/legal/privacy');
    $privacy->assertOk();
    $privacy->assertSee('Privacy Policy');

    $terms = $this->get('/legal/terms');
    $terms->assertOk();
    $terms->assertSee('Terms of Service');

    $imprint = $this->get('/legal/imprint');
    $imprint->assertOk();
    $imprint->assertSee('ternis-edv.de');
    $imprint->assertSee('xpsystems.eu');
    $imprint->assertSee('mtex.dev');
    $imprint->assertSee('dnbx.de');
});

test('invalid legal page returns 404', function () {
    $response = $this->get('/legal/unknown-policy');
    $response->assertNotFound();
});
