<?php

namespace AuroraWebSoftware\LogiAudit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PruneHistoryCommand extends Command
{
    protected $signature = 'history:prune {days=30 : Number of days before which records will be deleted}';
    protected $description = 'Delete all history records older than X days (created_at <= X days ago)';

    public function handle()
    {
        $days = (int) $this->argument('days');
        if ($days <= 0) {
            $this->error('Please provide a positive number of days.');
            return self::INVALID;
        }

        $cutoff = Carbon::now()->subDays($days);
        $batchSize = 500;
        $totalDeleted = 0;
        $batchNumber = 1;

        $query = DB::table('logiaudit_history')
            ->where('created_at', '<=', $cutoff);

        do {
            $deleted = $query->take($batchSize)->delete();

            if ($deleted > 0) {
                $this->line("Batch #{$batchNumber}: Deleted {$deleted} history records. Sleeping for 10ms...");
                usleep(10000);
                $batchNumber++;
                $totalDeleted += $deleted;
            }
        } while ($deleted !== 0);

        $summary = "Prune completed. Deleted total {$totalDeleted} history records older than {$days} days.";
        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}
