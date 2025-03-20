<?php

namespace AuroraWebSoftware\LogiAudit\Models;

use Illuminate\Database\Eloquent\Model;

class LogiAuditLog extends Model
{
    protected $table = 'logiaudit_logs';

    protected $fillable = [
        'level',
        'message',
        'model_id',
        'model_type',
        'trace_id',
        'context',
        'ip_address',
        'deletable',
        'deleted_at',
    ];

    protected $casts = [
        'context' => 'array',
        'deletable' => 'boolean',
    ];
}
