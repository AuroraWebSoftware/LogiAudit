<?php

use AuroraWebSoftware\LogiAudit\Models\LogiAuditHistory;
use AuroraWebSoftware\LogiAudit\Tests\Jobs\StoreHistoryJob;
use AuroraWebSoftware\LogiAudit\Tests\Models\TestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['queue.default' => 'database']);

    Artisan::call('migrate:fresh');

    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->nullable();
        $table->string('excluded_field')->nullable();
    });

    if (! Schema::hasTable('jobs')) {
        Artisan::call('queue:table');
        Artisan::call('migrate');
    }

    if (! Schema::hasTable('failed_jobs')) {
        Artisan::call('queue:failed-table');
        Artisan::call('migrate');
    }

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('user')->andReturn((object) ['id' => 1]);

    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    app()->singleton('request', fn () => $request);
});

it('dispatches and processes StoreHistoryJob entries with one failure', function () {
    $model = TestModel::create([
        'name' => 'Created name',
        'status' => 'active',
        'excluded_field' => 'nope',
    ]);

    $model->update([
        'name' => 'Updated name',
        'status' => 'inactive',
    ]);

    $model->delete();

    StoreHistoryJob::dispatch(
        'fail',
        'test_models',
        TestModel::class,
        999,
        ['name'],
        null,
        [['name' => 'Failing']],
        1,
        '127.0.0.1'
    );

    $queuedJobs = DB::table('jobs')->get();
    dump('🔄 Queued Jobs:', $queuedJobs->toArray());

    $queueName = config('logiaudit.history_queue_name', 'logiaudit');
    Artisan::call("queue:work --queue={$queueName} --tries=3 --stop-when-empty");

    $auditLogs = LogiAuditHistory::orderBy('id')->get();
    dump('📜 Audit Logs:', $auditLogs->toArray());

    $failedJobs = DB::table('failed_jobs')->get();
    dump('❌ Failed Jobs:', $failedJobs->toArray());

    expect($auditLogs)->toHaveCount(3);
    expect($auditLogs->pluck('action')->toArray())->toBe(['created', 'updated', 'deleted']);
    expect($failedJobs)->toHaveCount(1);
});
