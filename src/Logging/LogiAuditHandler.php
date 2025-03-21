<?php

namespace AuroraWebSoftware\LogiAudit\Logging;

use AuroraWebSoftware\LogiAudit\Jobs\StoreLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class LogiAuditHandler
{
    public function __invoke(array $config)
    {
        $logger = new Logger('logiaudit');

        $logger->pushHandler(new class extends AbstractProcessingHandler
        {
            protected function write(LogRecord $record): void
            {
                $context = $record->context;

                $modelId = $context['model_id'] ?? null;
                $modelType = $context['model_type'] ?? null;
                $traceId = $context['trace_id'] ?? null;
                $ipAddress = $context['ip_address'] ?? null;
                $deletable = $context['deletable'] ?? true;
                $deleteAfterDays = $context['delete_after_days'] ?? null;
                unset(
                    $context['model_id'],
                    $context['model_type'],
                    $context['trace_id'],
                    $context['ip_address'],
                    $context['deletable'],
                    $context['delete_after_days']
                );

                if (isset($context['context']) && is_array($context['context'])) {
                    $context = $context['context'];
                }

                if (empty($context)) {
                    $context = null;
                }

                dispatch(new StoreLogJob(
                    strtolower($record->level->name),
                    $record->message,
                    $modelId,
                    $modelType,
                    $traceId,
                    $context,
                    $ipAddress,
                    $deletable,
                    $deleteAfterDays
                ));
            }
        });

        return $logger;
    }
}
