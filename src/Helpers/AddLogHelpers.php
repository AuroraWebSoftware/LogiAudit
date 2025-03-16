<?php

use AuroraWebSoftware\LogiAudit\Jobs\StoreLogJob;

if (!function_exists('addLog')) {
    function addLog(string $level, string $message, array $options = [])
    {
        $modelId = $options['model_id'] ?? null;
        $modelType = $options['model_type'] ?? null;
        $traceId = $options['trace_id'] ?? null;
        $context = $options['context'] ?? [];
        $ipAdress = $options['ip_adress'] ?? null;
        $deletable = $options['deletable'] ?? true;

        StoreLogJob::dispatch(
            $level,
            $message,
            $modelId,
            $modelType,
            $traceId,
            $context,
            $ipAdress,
            $deletable
        )->onQueue('default');

    }
}
