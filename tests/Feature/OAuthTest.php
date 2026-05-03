<?php

use Laravel\Socialite\Facades\Socialite;

test('it redirects to github', function () {
    $response = $this->get(route('social.redirect', 'github'));
    
    $response->assertStatus(302);
    $this->assertStringContainsString('github.com/login/oauth/authorize', $response->getTargetUrl());
});
