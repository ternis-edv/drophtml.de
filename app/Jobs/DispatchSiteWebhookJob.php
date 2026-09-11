<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchSiteWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Site $site,
        public string $event,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ActivityLogService $activityLogService): void
    {
        $url = $this->site->webhook_url;
        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        $payload = [
            'event' => $this->event,
            'site' => [
                'id' => $this->site->id,
                'slug' => $this->site->slug,
                'original_name' => $this->site->original_name,
                'status' => $this->site->status,
                'views' => $this->site->views,
            ],
            'timestamp' => now()->toIso8601String(),
            'data' => $this->data,
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'DropHTML-Webhook/1.0',
            'X-DropHTML-Event' => $this->event,
        ];

        if (! empty($this->site->webhook_secret)) {
            $headers['X-DropHTML-Signature'] = 'sha256='.hash_hmac('sha256', $payloadJson, $this->site->webhook_secret);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($payloadJson, 'application/json')
                ->post($url);

            if ($response->successful()) {
                $activityLogService->log(
                    $this->site->user,
                    $this->site,
                    'webhook.dispatched',
                    "Outgoing webhook for '{$this->event}' successfully delivered (HTTP {$response->status()})."
                );
            } else {
                Log::warning("Webhook delivery for site {$this->site->slug} to {$url} returned HTTP {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::error("Failed to dispatch webhook for site {$this->site->slug} to {$url}: {$e->getMessage()}");
            throw $e;
        }
    }
}
