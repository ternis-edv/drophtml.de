<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Models\Site;
use App\Services\DomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DomainController extends Controller
{
    public function __construct(
        protected DomainService $domainService
    ) {}

    public function index(Request $request, Site $site)
    {
        Gate::authorize('view', $site);

        return DomainResource::collection($site->domains);
    }

    public function store(StoreDomainRequest $request, Site $site)
    {
        Gate::authorize('update', $site);

        $validated = $request->validated();

        $domain = $this->domainService->createDomain(
            $site,
            $validated['domain'],
            $validated['is_custom'] ?? true,
            $request->user()
        );

        return new DomainResource($domain);
    }

    public function show(Request $request, Site $site, Domain $domain)
    {
        if ($domain->site_id !== $site->id) {
            abort(404, 'Domain does not belong to this site.');
        }

        Gate::authorize('view', $domain);

        return new DomainResource($domain);
    }

    public function destroy(Request $request, Site $site, Domain $domain)
    {
        if ($domain->site_id !== $site->id) {
            abort(404, 'Domain does not belong to this site.');
        }

        Gate::authorize('delete', $domain);

        $this->domainService->deleteDomain($domain, $request->user());

        return response()->json(['message' => 'Domain deleted successfully.']);
    }
}
