<?php

namespace AuroraWebSoftware\LogiAudit\Commands;

use AuroraWebSoftware\LogiAudit\Jobs\PruneHistoryJob;
use Illuminate\Console\Command;

class PruneHistoryCommand extends Command
{
    protected $signature = 'history:prune {days=30 : Number of days before which records will be deleted}';

    protected $description = 'Dispatch a job to delete history records older than X days';

    public function handle()
    {
        $days = (int) $this->argument('days');

        if ($days <= 0) {
            $this->error('Please provide a positive number of days.');

            return Command::INVALID;
        }

        PruneHistoryJob::dispatch($days);

        $this->info("PruneHistoryJob dispatched successfully to delete history older than {$days} days.");

        return Command::SUCCESS;
    }
}
