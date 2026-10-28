<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Http\Resources\DeploymentResource;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use App\Services\SiteDeploymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class SiteController extends Controller
{
    public function __construct(
        protected SiteDeploymentService $siteDeploymentService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Site::class);

        $sites = $request->user()->sites()->with('domains')->latest()->paginate(15);

        return SiteResource::collection($sites);
    }

    public function store(StoreSiteRequest $request)
    {
        Gate::authorize('create', Site::class);

        $validated = $request->validated();
        $user = $request->user();

        try {
            if ($request->hasFile('file')) {
                $site = $this->siteDeploymentService->createSiteFromUpload(
                    $request->file('file'),
                    $validated['original_name'],
                    $user,
                    $validated
                );
            } elseif ($request->filled('files') && is_array($request->input('files'))) {
                $site = $this->siteDeploymentService->createSiteFromFiles(
                    $request->input('files'),
                    $validated['original_name'],
                    $user,
                    $validated
                );
            } else {
                $content = $request->input('html') ?? $request->input('content');
                if (empty($content)) {
                    $content = "<!DOCTYPE html><html><head><title>{$validated['original_name']}</title></head><body><h1>{$validated['original_name']}</h1><p>Created via DropHTML API.</p></body></html>";
                }

                $site = $this->siteDeploymentService->createSiteFromContent(
                    $content,
                    $validated['original_name'],
                    $user,
                    $validated
                );
            }
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new SiteResource($site);
    }

    public function show(Request $request, Site $site)
    {
        Gate::authorize('view', $site);

        $site->load(['domains', 'deployments']);

        return new SiteResource($site);
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        Gate::authorize('update', $site);

        $validated = $request->validated();

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
        Gate::authorize('delete', $site);

        $this->siteDeploymentService->deleteSite($site, $request->user());

        return response()->json(['message' => 'Site deleted successfully.']);
    }

    public function deploy(Request $request, Site $site)
    {
        Gate::authorize('update', $site);

        $request->validate([
            'file' => 'nullable|file|max:51200',
            'html' => 'nullable|string',
            'content' => 'nullable|string',
            'files' => 'nullable|array',
            'commit_message' => 'nullable|string|max:255',
        ]);

        $commitMessage = $request->input('commit_message');
        $user = $request->user();

        try {
            if ($request->hasFile('file')) {
                $deployment = $this->siteDeploymentService->deployToExistingSite(
                    $site,
                    $request->file('file'),
                    $user,
                    $commitMessage,
                    'api'
                );
            } elseif ($request->filled('files') && is_array($request->input('files'))) {
                $deployment = $this->siteDeploymentService->deployFilesToExistingSite(
                    $site,
                    $request->input('files'),
                    $user,
                    $commitMessage,
                    'api'
                );
            } elseif ($request->filled('html') || $request->filled('content')) {
                $html = $request->input('html') ?? $request->input('content');
                $deployment = $this->siteDeploymentService->deployContentToExistingSite(
                    $site,
                    $html,
                    $user,
                    $commitMessage,
                    'api'
                );
            } else {
                return response()->json([
                    'message' => 'A file upload, html/content string, or files array is required to deploy.',
                ], 422);
            }
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new DeploymentResource($deployment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Duplicate/clone an existing site.
     */
    public function duplicate(Request $request, Site $site)
    {
        Gate::authorize('update', $site);

        $request->validate([
            'custom_slug' => 'nullable|string|max:64|alpha_dash',
        ]);

        $clonedSite = $this->siteDeploymentService->duplicateSite(
            $site,
            $request->user(),
            $request->input('custom_slug')
        );

        return (new SiteResource($clonedSite))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Download the site files as a ZIP archive.
     * Optionally includes cloud media assets if ?include_media=1.
     */
    public function download(Request $request, Site $site)
    {
        Gate::authorize('view', $site);

        $includeMedia = $request->boolean('include_media', false) || $request->boolean('media', false);

        return $this->siteDeploymentService->downloadZip($site, $includeMedia);
    }
}
