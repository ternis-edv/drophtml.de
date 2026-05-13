<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Fortify\Features;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\App;

class ConfirmPasswordIfSet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->password_set_at &&
            Features::canManageTwoFactorAuthentication() &&
            Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            return App::make(RequirePassword::class)->handle($request, $next);
        }

        return $next($request);
    }
}
