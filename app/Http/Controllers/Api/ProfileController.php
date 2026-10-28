<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\MediaStorageService;
use App\Services\SiteDeploymentService;
use App\Services\StorageUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        protected SiteDeploymentService $siteDeploymentService
    ) {}

    public function show(Request $request)
    {
        return new ProfileResource($request->user());
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new ProfileResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->password_set_at) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        foreach ($user->sites as $site) {
            $this->siteDeploymentService->deleteSite($site);
        }

        foreach ($user->mediaAssets()->withTrashed()->get() as $asset) {
            app(MediaStorageService::class)->delete($asset);
        }

        // Clean up standalone user cloud media assets
        Storage::disk('media')->deleteDirectory("users/{$user->id}");
        $user->mediaAssets()->forceDelete();

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }

    /**
     * Get storage usage telemetry and quota for authenticated user.
     */
    public function storage(Request $request, StorageUsageService $storageUsageService)
    {
        $stats = $storageUsageService->getUserStorageStats($request->user());

        return response()->json($stats);
    }
}
