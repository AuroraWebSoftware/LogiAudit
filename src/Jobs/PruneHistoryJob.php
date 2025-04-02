<?php

namespace AuroraWebSoftware\LogiAudit\Jobs;

use AuroraWebSoftware\LogiAudit\Models\LogiAuditHistory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PruneHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $days;

    public function __construct(int $days)
    {
        $this->days = $days;
    }

    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays($this->days);


        $deletedCount = LogiAuditHistory::where('created_at', '<=', $cutoff)->delete();

        Log::info("PruneHistoryJob executed. Deleted {$deletedCount} history records older than {$this->days} days.");
    }
}
