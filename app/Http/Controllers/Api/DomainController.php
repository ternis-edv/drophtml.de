<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Models\Site;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        return DomainResource::collection($site->domains);
    }

    public function store(Request $request, Site $site)
    {
        if ($site->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'is_custom' => ['boolean'],
        ]);

        $domainName = strtolower(rtrim(preg_replace('#^https?://#', '', trim($validated['domain'])), '/'));

        $domain = $site->domains()->create([
            'domain' => $domainName,
            'is_custom' => $validated['is_custom'] ?? true,
        ]);

        return new DomainResource($domain);
    }

    public function show(Request $request, Site $site, Domain $domain)
    {
        if ($site->user_id !== $request->user()->id || $domain->site_id !== $site->id) {
            abort(403);
        }

        return new DomainResource($domain);
    }

    public function destroy(Request $request, Site $site, Domain $domain)
    {
        if ($site->user_id !== $request->user()->id || $domain->site_id !== $site->id) {
            abort(403);
        }

        $domain->delete();

        return response()->json(['message' => 'Domain deleted successfully.']);
    }
}
