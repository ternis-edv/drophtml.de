<?php

namespace App\Ai;

use Illuminate\Support\Facades\DB;

class Budget
{
    public function reserve(string $user): void
    {
        // Conditional SQL increments serialize concurrent requests across all web workers.
        DB::transaction(function () use ($user) {
            $buckets = [
                'global:'.now('UTC')->format('Y-m-d') => 100,
                'user:'.$user.':'.now('UTC')->format('Y-m-d') => 10,
                'minute:'.$user.':'.now('UTC')->format('Y-m-d-H-i') => 1,
            ];
            foreach ($buckets as $key => $limit) {
                DB::table('assistant_budgets')->insertOrIgnore(['key' => $key, 'used' => 0]);
                abort_unless(DB::table('assistant_budgets')->where('key', $key)->where('used', '<', $limit)->increment('used') === 1, 429, 'AI usage limit reached. Try again later.');
            }
        });
    }
}
