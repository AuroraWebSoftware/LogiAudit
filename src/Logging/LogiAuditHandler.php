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

                unset(
                    $context['model_id'],
                    $context['model_type'],
                    $context['trace_id'],
                    $context['ip_address'],
                    $context['deletable']
                );

                if (isset($context['context']) && is_array($context['context'])) {
                    $context = $context['context'];
                }

                dispatch(new StoreLogJob(
                    strtolower($record->level->name),
                    $record->message,
                    $modelId,
                    $modelType,
                    $traceId,
                    $context,
                    $ipAddress,
                    $deletable
                ))->onQueue('default');
            }
        });

        return $logger;
    }
}
