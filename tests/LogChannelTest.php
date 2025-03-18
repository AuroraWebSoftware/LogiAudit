<?php

use AuroraWebSoftware\LogiAudit\Models\LogiAuditLog;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['queue.default' => 'database']);

    Artisan::call('migrate:refresh');

    dump('✅ Database migrations completed in PostgreSQL...');
    dump('✅ Queue Driver: '.config('queue.default'));

    $this->db = new DB;
    $this->db->addConnection(config('database.connections.pgsql'));
    $this->db->setAsGlobal();
    $this->db->bootEloquent();

    if (! Schema::hasTable('jobs')) {
        Artisan::call('queue:table');
        Artisan::call('migrate');
        dump("✅ 'jobs' table created in PostgreSQL...");
    }

    if (! Schema::hasTable('failed_jobs')) {
        Artisan::call('queue:failed-table');
        Artisan::call('migrate');
        dump("✅ 'failed_jobs' table created in PostgreSQL...");
    }

    config([
        'logging.channels.logiaudit' => [
            'driver' => 'custom',
            'via' => \AuroraWebSoftware\LogiAudit\Logging\LogiAuditHandler::class,
        ],
    ]);
});

it('logs multiple messages, queues them, processes jobs, and verifies results', function () {
    dump('🚀 Logging started...');

    Log::channel('logiaudit')->info('First log message', [
        'trace_id' => 'trace-1',
        'model_id' => 100,
        'model_type' => 'App\Models\User',
        'context' => ['foo' => 'bar'],
        'deletable' => true,
    ]);

    Log::channel('logiaudit')->warning('Second log message', [
        'trace_id' => 'trace-2',
        'model_id' => 200,
        'model_type' => 'App\Models\Order',
        'context' => ['action' => 'update'],
        'deletable' => false,
    ]);

    Log::channel('logiaudit')->error(null, [
        'trace_id' => 'trace-fail',
        'model_id' => 300,
        'model_type' => 'App\Models\Product',
        'context' => ['action' => 'delete'],
        'deletable' => true,
    ]);

    dump('✅ Log messages sent!');

    $queuedJobs = DB::table('jobs')->get();
    dump('🔍 Queued Jobs in database:', json_encode($queuedJobs, JSON_PRETTY_PRINT));
    expect($queuedJobs)->toHaveCount(3);

    Artisan::call('queue:work --queue=default --tries=3 --stop-when-empty');

    $remainingJobs = DB::table('jobs')->get();
    dump('🔍 Remaining Jobs in Queue (After Processing):', json_encode($remainingJobs, JSON_PRETTY_PRINT));
    expect($remainingJobs)->toBeEmpty();

    $failedJobs = DB::table('failed_jobs')->get();
    dump('❌ Failed Jobs:', json_encode($failedJobs, JSON_PRETTY_PRINT));
    expect($failedJobs)->toHaveCount(1);

    $logs = LogiAuditLog::all();
    dump('✅ Log records in database:', json_encode($logs, JSON_PRETTY_PRINT));
    expect($logs)->toHaveCount(2);

    $log1 = LogiAuditLog::where('trace_id', 'trace-1')->first();
    $decodedContext1 = json_decode($log1->context, true); // JSON formatında kaydedildiği için decode ediyoruz

    expect($log1)->not->toBeNull()
        ->and($log1->level)->toBe('info')
        ->and($log1->message)->toBe('First log message')
        ->and($log1->trace_id)->toBe('trace-1')
        ->and($log1->model_id)->toBe(100)
        ->and($log1->model_type)->toBe('App\Models\User')
        ->and($decodedContext1)->toHaveKey('foo')
        ->and($decodedContext1['foo'])->toBe('bar')
        ->and($log1->deletable)->toBeTrue();

    $log2 = LogiAuditLog::where('trace_id', 'trace-2')->first();
    $decodedContext2 = json_decode($log2->context, true);

    expect($log2)->not->toBeNull()
        ->and($log2->level)->toBe('warning')
        ->and($log2->message)->toBe('Second log message')
        ->and($log2->trace_id)->toBe('trace-2')
        ->and($log2->model_id)->toBe(200)
        ->and($log2->model_type)->toBe('App\Models\Order')
        ->and($decodedContext2)->toHaveKey('action')
        ->and($decodedContext2['action'])->toBe('update')
        ->and($log2->deletable)->toBeFalse();
});
