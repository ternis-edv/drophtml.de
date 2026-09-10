<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeploymentResource;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeploymentController extends Controller
{
    public function index(Request $request, Site $site)
    {
        Gate::authorize('view', $site);

        $deployments = $site->deployments()->latest()->paginate(15);

        return DeploymentResource::collection($deployments);
    }

    public function show(Request $request, Site $site, Deployment $deployment)
    {
        if ($deployment->site_id !== $site->id) {
            abort(404, 'Deployment does not belong to this site.');
        }

        Gate::authorize('view', $deployment);

        return new DeploymentResource($deployment);
    }
}
