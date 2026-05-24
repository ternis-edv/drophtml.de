<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeploymentResource;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    public function index(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        $deployments = $site->deployments()->latest()->paginate(15);

        return DeploymentResource::collection($deployments);
    }

    public function show(Request $request, Site $site, Deployment $deployment)
    {
        if ($site->user_id !== $request->user()->id || $deployment->site_id !== $site->id) {
            abort(403);
        }

        return new DeploymentResource($deployment);
    }
}
