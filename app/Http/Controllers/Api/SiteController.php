<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'file' => ['nullable', 'file', 'max:51200'],
            'github_repo' => ['nullable', 'string', 'max:255'],
            'github_repo_full_name' => ['nullable', 'string', 'max:255'],
            'github_branch' => ['nullable', 'string', 'max:255'],
            'auto_deploy' => ['boolean'],
            'is_permanent' => ['boolean'],
            'is_password_protected' => ['boolean'],
            'site_password' => ['nullable', 'string', 'min:4'],
        ]);

        $slug = Str::slug(pathinfo($validated['original_name'], PATHINFO_FILENAME));
        if (empty($slug)) {
            $slug = 'site';
        }
        $slug .= '-'.Str::random(6);
        $path = 'sites/'.$slug;

        Storage::disk('public')->makeDirectory($path);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($file->getRealPath()) === true) {
                    $extractPath = Storage::disk('public')->path($path);
                    $zip->extractTo($extractPath);
                    $zip->close();

                    // Hoist single inner directory
                    $items = array_diff(scandir($extractPath), ['.', '..']);
                    if (count($items) === 1) {
                        $innerDir = $extractPath.DIRECTORY_SEPARATOR.array_shift($items);
                        if (is_dir($innerDir)) {
                            $files = array_diff(scandir($innerDir), ['.', '..']);
                            foreach ($files as $f) {
                                rename("{$innerDir}".DIRECTORY_SEPARATOR."{$f}", "{$extractPath}".DIRECTORY_SEPARATOR."{$f}");
                            }
                            rmdir($innerDir);
                        }
                    }
                }
            } else {
                $file->storeAs($path, $file->getClientOriginalName(), 'public');
            }
        } else {
            // Default placeholder if no file uploaded
            Storage::disk('public')->put("{$path}/index.html", "<!DOCTYPE html><html><head><title>{$validated['original_name']}</title></head><body><h1>{$validated['original_name']}</h1><p>Created via DropHTML API.</p></body></html>");
        }

        $repoName = $validated['github_repo_full_name'] ?? $validated['github_repo'] ?? null;
        $isPermanent = $validated['is_permanent'] ?? false;

        $site = $request->user()->sites()->create([
            'slug' => $slug,
            'original_name' => $validated['original_name'],
            'status' => 'active',
            'path' => $path,
            'is_permanent' => $isPermanent,
            'expires_at' => $isPermanent ? null : now()->addDays(30),
            'github_repo_full_name' => $repoName,
            'github_branch' => $validated['github_branch'] ?? null,
            'auto_deploy' => $validated['auto_deploy'] ?? false,
            'is_password_protected' => $validated['is_password_protected'] ?? false,
            'site_password' => isset($validated['site_password']) ? Hash::make($validated['site_password']) : null,
        ]);

        return new SiteResource($site);
    }

    public function show(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        $site->load(['domains', 'deployments']);

        return new SiteResource($site);
    }

    public function update(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'in:active,suspended'],
            'is_permanent' => ['sometimes', 'boolean'],
            'auto_deploy' => ['sometimes', 'boolean'],
            'is_password_protected' => ['sometimes', 'boolean'],
            'site_password' => ['nullable', 'string', 'min:4'],
        ]);

        if (array_key_exists('site_password', $validated)) {
            $validated['site_password'] = ! empty($validated['site_password']) ? Hash::make($validated['site_password']) : null;
        }

        if (isset($validated['is_permanent'])) {
            $validated['expires_at'] = $validated['is_permanent'] ? null : ($site->expires_at ?: now()->addDays(30));
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
