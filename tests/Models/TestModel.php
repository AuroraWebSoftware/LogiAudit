<?php

namespace AuroraWebSoftware\LogiAudit\Tests\Models;

use AuroraWebSoftware\LogiAudit\Tests\Traits\LogiAuditTrait;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use LogiAuditTrait;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    protected $excludedColumns = ['excluded_field'];

    protected $excludedEvents = [];
}
