<?php

namespace AuroraWebSoftware\LogiAudit\Models;

use Illuminate\Database\Eloquent\Model;

class LogiAuditHistory extends Model
{
    protected $table = 'logiaudit_history';

    protected $guarded = [];

    protected $casts = [
        'column' => 'array',
        'old_value' => 'array',
        'new_value' => 'array',
    ];
}
