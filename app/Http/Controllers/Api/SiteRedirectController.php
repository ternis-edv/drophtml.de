<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteRedirectResource;
use App\Models\Site;
use App\Models\SiteRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SiteRedirectController extends Controller
{
    public function index(Request $request, Site $site)
    {
        Gate::authorize('view', $site);

        return SiteRedirectResource::collection($site->redirects);
    }

    public function store(Request $request, Site $site)
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'from_path' => ['required', 'string', 'max:2048'],
            'to_url' => [
                'required',
                'string',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $lower = strtolower(trim($value));
                    if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:') || str_starts_with($lower, 'vbscript:') || str_starts_with($lower, 'file:')) {
                        $fail('The destination target URL contains an unsafe protocol.');
                    }
                },
            ],
            'status_code' => ['nullable', 'integer', Rule::in([200, 301, 302, 307, 308])],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $fromPath = '/'.ltrim(trim($validated['from_path']), '/');

        $redirect = $site->redirects()->create([
            'from_path' => $fromPath,
            'to_url' => trim($validated['to_url']),
            'status_code' => $validated['status_code'] ?? 301,
            'is_active' => $validated['is_active'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return new SiteRedirectResource($redirect);
    }

    public function show(Request $request, Site $site, SiteRedirect $redirect)
    {
        if ($redirect->site_id !== $site->id) {
            abort(404, 'Redirect does not belong to this site.');
        }

        Gate::authorize('view', $site);

        return new SiteRedirectResource($redirect);
    }

    public function update(Request $request, Site $site, SiteRedirect $redirect)
    {
        if ($redirect->site_id !== $site->id) {
            abort(404, 'Redirect does not belong to this site.');
        }

        Gate::authorize('update', $site);

        $validated = $request->validate([
            'from_path' => ['sometimes', 'required', 'string', 'max:2048'],
            'to_url' => [
                'sometimes',
                'required',
                'string',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $lower = strtolower(trim($value));
                    if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:') || str_starts_with($lower, 'vbscript:') || str_starts_with($lower, 'file:')) {
                        $fail('The destination target URL contains an unsafe protocol.');
                    }
                },
            ],
            'status_code' => ['nullable', 'integer', Rule::in([200, 301, 302, 307, 308])],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['from_path'])) {
            $validated['from_path'] = '/'.ltrim(trim($validated['from_path']), '/');
        }

        if (isset($validated['to_url'])) {
            $validated['to_url'] = trim($validated['to_url']);
        }

        $redirect->update($validated);

        return new SiteRedirectResource($redirect);
    }

    public function destroy(Request $request, Site $site, SiteRedirect $redirect)
    {
        if ($redirect->site_id !== $site->id) {
            abort(404, 'Redirect does not belong to this site.');
        }

        Gate::authorize('update', $site);

        $redirect->delete();

        return response()->json(['message' => 'Redirect rule deleted successfully.']);
    }
}
