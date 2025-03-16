<?php

namespace AuroraWebSoftware\LogiAudit\Jobs;

use AuroraWebSoftware\LogiAudit\Models\LogiAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
        $deletedCount = LogiAuditLog::where('deletable', true)->delete();

        Log::info("PruneLogJob executed. Deleted {$deletedCount} log records.");
    }
}
