<?php

namespace App\Ai;

use Illuminate\Support\Facades\DB;

class Budget
{
    public function status(string $user): array
    {
        $now = now('UTC');
        $used = (int) DB::table('assistant_budgets')->where('key', 'user:'.$user.':'.$now->format('Y-m-d'))->value('used');
        $global = (int) DB::table('assistant_budgets')->where('key', 'global:'.$now->format('Y-m-d'))->value('used');
        $minute = (int) DB::table('assistant_budgets')->where('key', 'minute:'.$user.':'.$now->format('Y-m-d-H-i'))->value('used');

        return ['remaining' => max(0, 10 - $used), 'shared_available' => $global < 100,
            'retry_at' => $minute ? $now->copy()->addMinute()->startOfMinute()->timestamp : 0,
            'resets_at' => $now->copy()->addDay()->startOfDay()->toIso8601String()];
    }

    public function reserve(string $user): void
    {
        // Conditional SQL increments serialize concurrent requests across all web workers.
        DB::transaction(function () use ($user) {
            $now = now('UTC');
            $buckets = [
                'global:'.$now->format('Y-m-d') => 100,
                'user:'.$user.':'.$now->format('Y-m-d') => 10,
                'minute:'.$user.':'.$now->format('Y-m-d-H-i') => 1,
            ];
            foreach ($buckets as $key => $limit) {
                DB::table('assistant_budgets')->insertOrIgnore(['key' => $key, 'used' => 0]);
                abort_unless(DB::table('assistant_budgets')->where('key', $key)->where('used', '<', $limit)->increment('used') === 1, 429, 'AI usage limit reached. Try again later.');
            }
        });
    }
}
