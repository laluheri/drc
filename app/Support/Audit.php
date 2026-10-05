<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Audit
{
    public static function record(string $action, string $module, ?int $id = null): void
    {
        DB::table('logs')->insert(['user_id' => auth()->id(), 'action' => $action, 'module' => $module, 'record_id' => $id, 'ip_address' => request()->ip(), 'user_agent' => mb_substr(request()->userAgent() ?? '', 0, 255), 'created_at' => now()]);
    }
}
