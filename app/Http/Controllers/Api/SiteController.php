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

        if ($request->hasFile('file')) {
            $site = $this->siteDeploymentService->createSiteFromUpload(
                $request->file('file'),
                $validated['original_name'],
                $user,
                $validated
            );
        } else {
            $placeholderHtml = "<!DOCTYPE html><html><head><title>{$validated['original_name']}</title></head><body><h1>{$validated['original_name']}</h1><p>Created via DropHTML API.</p></body></html>";

            $site = $this->siteDeploymentService->createSiteFromContent(
                $placeholderHtml,
                $validated['original_name'],
                $user,
                $validated
            );
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
            'file' => 'required|file|max:51200',
            'commit_message' => 'nullable|string|max:255',
        ]);

        $deployment = $this->siteDeploymentService->deployToExistingSite(
            $site,
            $request->file('file'),
            $request->user(),
            $request->input('commit_message'),
            'api'
        );

        return (new DeploymentResource($deployment))
            ->response()
            ->setStatusCode(201);
    }
}
