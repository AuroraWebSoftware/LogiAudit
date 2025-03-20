<?php

use AuroraWebSoftware\LogiAudit\Tests\Jobs\StoreLogJob;

if (! function_exists('addLogT')) {
    function addLogT(string $level, string $message, array $options = [])
    {
        $modelId = $options['model_id'] ?? null;
        $modelType = $options['model_type'] ?? null;
        $traceId = $options['trace_id'] ?? null;
        $context = $options['context'] ?? null;
        $ipAddress = $options['ip_address'] ?? null;
        $deletable = $options['deletable'] ?? true;
        $deleteAfterDays = $options['delete_after_days'] ?? null;

        StoreLogJob::dispatch(
            $level,
            $message,
            $modelId,
            $modelType,
            $traceId,
            $context,
            $ipAddress,
            $deletable,
            $deleteAfterDays
        );

    }
}
