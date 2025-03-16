<?php

return [
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
        ],

        'logiaudit' => [
            'driver' => 'custom',
            'via' => AuroraWebSoftware\LogiAudit\Logging\LogiAuditLogger::class,
        ],
    ],

];
