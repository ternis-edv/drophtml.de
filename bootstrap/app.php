<?php

use App\Models\SiteErrorPage;
use App\Services\SiteErrorPageService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/webhooks/github',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->expectsJson() || ! in_array($response->getStatusCode(), SiteErrorPage::STATUSES, true)) {
                return $response;
            }

            // A failing database/storage connection must not break exception rendering.
            return rescue(function () use ($request, $response) {
                $pages = app(SiteErrorPageService::class);
                $context = $pages->context($request);

                if (! $context) {
                    return $response;
                }
                $siteResponse = $pages->response($request, $context['site'], $response->getStatusCode(), $context['baseUrl']);
                if ($response->headers->has('Retry-After')) {
                    $siteResponse->headers->set('Retry-After', $response->headers->get('Retry-After'));
                }

                return $siteResponse;
            }, $response, report: false);
        });
    })->create();
