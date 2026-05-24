<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = $request->user()->activityLogs()->latest()->paginate(20);
        return ActivityLogResource::collection($logs);
    }

    public function show(Request $request, ActivityLog $activityLog)
    {
        if ($activityLog->user_id !== $request->user()->id) {
            abort(403);
        }

        return new ActivityLogResource($activityLog);
    }
}
