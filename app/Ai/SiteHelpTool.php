<?php

namespace App\Ai;

use App\Models\Site;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SiteHelpTool implements Tool
{
    private int $calls = 0;

    public function __construct(private User $user, private bool $allowChanges = false) {}

    public function description(): string
    {
        return 'List up to 20 owned sites, inspect an owned site, or rename its display name. Rename requires explicit user request and enabled changes. Never changes content, domains, security or deployment.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['action' => $schema->string()->enum(['list', 'inspect', 'rename'])->required(),
            'site_id' => $schema->string(), 'name' => $schema->string()];
    }

    public function handle(Request $request): string
    {
        $id = Audit::record($this->user->id, 'tool', $request->all());
        try {
            abort_if(++$this->calls > 3, 429);
            $data = Validator::make($request->all(), [
                'action' => 'required|in:list,inspect,rename',
                'site_id' => 'required_unless:action,list|string|max:64',
                'name' => 'required_if:action,rename|string|max:100',
            ])->validate();
            if ($data['action'] === 'list') {
                Gate::forUser($this->user)->authorize('viewAny', Site::class);
                $result = $this->user->sites()->limit(20)->get(['id', 'original_name', 'status'])->toJson();
            } else {
                $site = $this->user->sites()->findOrFail($data['site_id']);
                Gate::forUser($this->user)->authorize($data['action'] === 'rename' ? 'update' : 'view', $site);
                if ($data['action'] === 'rename') {
                    abort_unless($this->allowChanges, 403);
                    $site->update(['original_name' => $data['name']]);
                }
                $result = $site->only(['id', 'original_name', 'status', 'is_spa', 'expires_at']);
                $result = json_encode($result, JSON_THROW_ON_ERROR);
            }
            Audit::finish($id, 'success');

            return $result;
        } catch (\Throwable $e) {
            Audit::finish($id, 'denied_or_failed');

            return 'Tool unavailable, invalid arguments, or permission denied.';
        }
    }
}
