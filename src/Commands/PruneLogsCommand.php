<?php

namespace AuroraWebSoftware\LogiAudit\Commands;

use AuroraWebSoftware\LogiAudit\Jobs\PruneLogJob;
use Illuminate\Console\Command;

class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune';

    protected $description = 'Delete all logs with deletable=true';

    public function handle()
    {
        PruneLogJob::dispatch();
        $this->info('PruneLogJob dispatched successfully.');
    }
}
