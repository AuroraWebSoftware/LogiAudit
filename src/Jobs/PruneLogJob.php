<?php

namespace AuroraWebSoftware\LogiAudit\Jobs;

use AuroraWebSoftware\LogiAudit\Models\LogiAuditLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PruneLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        $deletedCount = LogiAuditLog::query()
            ->where('deletable', true)
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $now)
            ->delete();

        Log::info("PruneLogJob executed. Deleted {$deletedCount} log records.");
    }
}
