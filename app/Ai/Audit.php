<?php

namespace App\Ai;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Audit
{
    public static function record(?string $user, string $kind, array $payload): int
    {
        return DB::table('assistant_audits')->insertGetId([
            'user_id' => $user, 'kind' => $kind, 'status' => 'started',
            'payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'created_at' => now(),
        ]);
    }

    public static function finish(int $id, string $status): void
    {
        DB::table('assistant_audits')->where('id', $id)->update(['status' => $status]);
    }
}
