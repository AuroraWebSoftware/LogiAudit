<?php

use AuroraWebSoftware\LogiAudit\Tests\Jobs\StoreLogJob;

if (! function_exists('addLogT')) {
    function addLogT(string $level, string $message, array $options = [])
    {
        try {
            $modelId = $options['model_id'] ?? null;
            $modelType = $options['model_type'] ?? null;
            $traceId = $options['trace_id'] ?? null;
            $ipAddress = $options['ip_address'] ?? null;
            $deletable = $options['deletable'] ?? true;
            $deleteAfterDays = $options['delete_after_days'] ?? null;

            unset(
                $options['model_id'],
                $options['model_type'],
                $options['trace_id'],
                $options['ip_address'],
                $options['deletable'],
                $options['delete_after_days']
            );

            $context = $options ?? null;

            StoreLogJob::dispatch(
                $level,
                $message,
                $modelId,
                $modelType,
                $traceId,
                $context,
                $ipAddress,
                $deletable,
                $deleteAfterDays,
                now()
            );
        } catch (Throwable $throwable) {

        }

    }
}
