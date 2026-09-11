<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaAssetRequest;
use App\Http\Resources\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MediaAssetController extends Controller
{
    public function __construct(
        protected MediaStorageService $mediaStorageService
    ) {}

    /**
     * List media assets.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', MediaAsset::class);

        $query = $request->user()->mediaAssets()->latest();

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->input('site_id'));
        }

        if ($request->filled('storage_driver')) {
            $query->where('storage_driver', $request->input('storage_driver'));
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->input('file_type'));
        }

        $assets = $query->paginate(20);

        return MediaAssetResource::collection($assets);
    }

    /**
     * Upload a new media asset to either Asset Storage (S3/R2) or Site Code.
     */
    public function store(StoreMediaAssetRequest $request)
    {
        Gate::authorize('create', MediaAsset::class);

        $validated = $request->validated();
        $user = $request->user();
        $site = null;

        if (! empty($validated['site_id'])) {
            $site = $user->sites()->findOrFail($validated['site_id']);
        }

        $storageDriver = $validated['storage_driver'] ?? 'asset_storage';
        $customSubdir = $validated['custom_subdir'] ?? null;

        $asset = $this->mediaStorageService->store(
            $request->file('file'),
            $user,
            $site,
            $storageDriver,
            $customSubdir
        );

        return (new MediaAssetResource($asset))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a media asset.
     */
    public function show(Request $request, MediaAsset $media)
    {
        Gate::authorize('view', $media);

        return new MediaAssetResource($media);
    }

    /**
     * Delete a media asset from storage.
     */
    public function destroy(Request $request, MediaAsset $media): JsonResponse
    {
        Gate::authorize('delete', $media);

        $this->mediaStorageService->delete($media, $request->user());

        return response()->json(['message' => 'Media asset deleted successfully.']);
    }

    /**
     * Migrate an asset between Asset Storage (S3/R2) and Code repository.
     */
    public function migrate(Request $request, MediaAsset $media)
    {
        Gate::authorize('update', $media);

        $request->validate([
            'target_driver' => 'required|string|in:asset_storage,code',
            'target_site_id' => 'nullable|string|exists:sites,id',
        ]);

        $targetDriver = $request->input('target_driver');
        $targetSite = null;

        if ($request->filled('target_site_id')) {
            $targetSite = $request->user()->sites()->findOrFail($request->input('target_site_id'));
        }

        $migrated = $this->mediaStorageService->migrate(
            $media,
            $targetDriver,
            $targetSite,
            $request->user()
        );

        return new MediaAssetResource($migrated);
    }
}
