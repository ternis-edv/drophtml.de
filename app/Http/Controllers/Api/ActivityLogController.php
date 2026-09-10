<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $logs = $request->user()->activityLogs()->latest()->paginate(20);

        return ActivityLogResource::collection($logs);
    }

    public function show(Request $request, ActivityLog $activityLog)
    {
        Gate::authorize('view', $activityLog);

        return new ActivityLogResource($activityLog);
    }
}
