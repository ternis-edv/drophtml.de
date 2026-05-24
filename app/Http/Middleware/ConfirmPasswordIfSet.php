<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class ConfirmPasswordIfSet
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
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
