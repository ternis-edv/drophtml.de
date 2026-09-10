<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\DeployGitHubRepositoryJob;
use App\Models\Site;
use App\Services\GitHubService;
use Illuminate\Http\Request;

class GithubWebhookController extends Controller
{
    public function __construct(
        protected GitHubService $gitHubService
    ) {}

    public function handle(Request $request)
    {
        // Verify GitHub webhook HMAC signature if secret configured
        if (! $this->gitHubService->verifyWebhookSignature($request)) {
            return response()->json(['message' => 'Invalid webhook signature'], 401);
        }

        $payload = $request->all();

        // Basic verification
        if (! isset($payload['repository']['full_name']) || ! isset($payload['ref'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $repoFullName = $payload['repository']['full_name'];
        $ref = $payload['ref'];
        $branch = str_replace('refs/heads/', '', $ref);

        $sites = Site::where('github_repo_full_name', $repoFullName)
            ->where('github_branch', $branch)
            ->where('auto_deploy', true)
            ->get();

        foreach ($sites as $site) {
            DeployGitHubRepositoryJob::dispatch($site, $payload);
        }

        return response()->json(['message' => 'Deployments triggered'], 200);
    }
}
