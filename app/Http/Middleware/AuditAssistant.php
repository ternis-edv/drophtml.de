<?php

namespace App\Http\Middleware;

use App\Ai\Audit;
use Closure;
use Illuminate\Http\Request;

class AuditAssistant
{
    public function handle(Request $request, Closure $next)
    {
        // Never persist arbitrary headers, credentials, or unbounded request bodies.
        $id = Audit::record($request->user()?->id, 'request', [
            'message' => is_string($request->input('message')) ? mb_substr($request->input('message'), 0, 2000) : '[invalid message type]',
            'allow_changes' => $request->boolean('allow_changes'),
        ]);
        try {
            $response = $next($request);
            Audit::finish($id, (string) $response->getStatusCode());

            return $response;
        } catch (\Throwable $e) {
            Audit::finish($id, method_exists($e, 'getStatusCode') ? (string) $e->getStatusCode() : 'failed');
            throw $e;
        }
    }
}
