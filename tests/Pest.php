<?php

use AuroraWebSoftware\LogiAudit\Tests\TestCase;
require_once __DIR__ . '/Helpers/helpers.php';

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    config(['queue.default' => 'database']);
})->in('AddLogHelperTest.php');
