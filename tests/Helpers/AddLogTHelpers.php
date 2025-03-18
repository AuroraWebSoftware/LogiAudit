<?php


use AuroraWebSoftware\LogiAudit\Tests\Jobs\StoreLogJob;

if (!function_exists('addLogT')) {
    function addLogT(string $level, string $message, array $options = [])
    {
        $modelId = $options['model_id'] ?? null;
        $modelType = $options['model_type'] ?? null;
        $traceId = $options['trace_id'] ?? null;
        $context = $options['context'] ?? [];
        $ipAddress = $options['ip_address'] ?? null;
        $deletable = $options['deletable'] ?? true;

        StoreLogJob::dispatch(
            $level,
            $message,
            $modelId,
            $modelType,
            $traceId,
            $context,
            $ipAddress,
            $deletable
        )->onQueue('default');

    }
}
