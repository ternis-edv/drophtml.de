<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $sites = $request->user()->sites()->with('domains')->latest()->paginate(15);
        return SiteResource::collection($sites);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
            'github_repo' => ['nullable', 'string', 'max:255'],
            'github_branch' => ['nullable', 'string', 'max:255'],
            'auto_deploy' => ['boolean'],
            'is_password_protected' => ['boolean'],
            'site_password' => ['nullable', 'string', 'min:4'],
        ]);

        $slug = Str::slug($validated['original_name']) . '-' . Str::random(6);
        $path = 'sites/' . $request->user()->id . '/' . $slug;

        $site = $request->user()->sites()->create([
            'slug' => $slug,
            'original_name' => $validated['original_name'],
            'status' => 'active',
            'path' => $path,
            'expires_at' => now()->addDays(7), // Default expiry
            'github_repo' => $validated['github_repo'] ?? null,
            'github_branch' => $validated['github_branch'] ?? null,
            'auto_deploy' => $validated['auto_deploy'] ?? false,
            'is_password_protected' => $validated['is_password_protected'] ?? false,
            'site_password' => isset($validated['site_password']) ? bcrypt($validated['site_password']) : null,
        ]);

        return new SiteResource($site);
    }

    public function show(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        $site->load('domains');
        return new SiteResource($site);
    }

    public function update(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'in:active,inactive'],
            'is_permanent' => ['sometimes', 'boolean'],
            'auto_deploy' => ['sometimes', 'boolean'],
            'is_password_protected' => ['sometimes', 'boolean'],
            'site_password' => ['nullable', 'string', 'min:4'],
        ]);

        if (isset($validated['site_password'])) {
            $validated['site_password'] = bcrypt($validated['site_password']);
        }

        $site->update($validated);

        return new SiteResource($site);
    }

    public function destroy(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($site->path) {
            Storage::disk('public')->deleteDirectory($site->path);
        }

        $site->delete();

        return response()->json(['message' => 'Site deleted successfully.']);
    }
}
