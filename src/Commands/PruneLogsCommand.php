<?php

namespace AuroraWebSoftware\LogiAudit\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune';

    protected $description = 'Delete all logs with deletable=true and deleted_at in the past.';

    public function handle()
    {
        $batchSize = 500;
        $now = Carbon::now();
        $totalDeleted = 0;
        $batchNumber = 1;

        $query = DB::table('logiaudit_logs')
            ->where('deletable', true)
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $now);

        do {
            $deleted = $query->take($batchSize)->delete();

            if ($deleted > 0) {
                $this->line("Batch #{$batchNumber}: Deleted {$deleted} records. Sleeping for 10ms...");
                usleep(10000);
                $batchNumber++;
                $totalDeleted += $deleted;
            }
        } while ($deleted !== 0);

        $summary = "Prune completed. Deleted total {$totalDeleted} log records.";
        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}
