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

test('legal pages render their own seo title and description', function () {
    $response = $this->get('/legal/privacy');

    $response->assertOk();
    $response->assertSee('<title>Privacy Policy - DropHTML.de</title>', false);
    $response->assertSee('<meta property="og:title" content="Privacy Policy - DropHTML.de">', false);
    $response->assertSee('<meta name="description" content="1. Introduction Welcome to DropHTML.de', false);
    $response->assertSee('<link rel="canonical" href="'.url('/legal/privacy').'">', false);

    $imprint = $this->get('/legal/imprint');
    $imprint->assertSee('<title>Imprint &amp; Legal Notice - DropHTML.de</title>', false);
});

test('invalid legal page returns 404', function () {
    $response = $this->get('/legal/unknown-policy');
    $response->assertNotFound();
});
