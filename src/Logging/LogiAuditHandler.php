<?php

namespace AuroraWebSoftware\LogiAudit\Logging;

use AuroraWebSoftware\LogiAudit\Tests\Jobs\StoreLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class LogiAuditHandler
{
    public function __invoke(array $config)
    {
        $logger = new Logger('logiaudit');

        $logger->pushHandler(new class extends AbstractProcessingHandler {
            protected function write(LogRecord $record): void
            {
                dump("🚀 LogiAuditHandler write() triggered!");

                $context = $record->context;
                $modelId = $context['model_id'] ?? null;
                $modelType = $context['model_type'] ?? null;
                unset($context['model_id'], $context['model_type']);

                dispatch(new StoreLogJob(
                    strtolower($record->level->name),
                    $record->message,
                    $modelId,
                    $modelType,
                    $context['trace_id'] ?? null,
                    $context ?? [],
                    $context['ip_address'] ?? null,
                    $context['deletable'] ?? true
                ))->onQueue(config('queue.connections.database_log'))
                    ->afterCommit();

                dump("✅ StoreLogJob queued successfully!");
            }
        });

        return $logger;
    }
}
