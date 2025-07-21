<?php

use AuroraWebSoftware\LogiAudit\Models\LogiAuditLog;
use AuroraWebSoftware\LogiAudit\Tests\Logging\LogiAuditHandler;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {

    config(['queue.default' => 'database']);

    $this->db = new DB;
    $this->db->addConnection(config('database.connections.'.config('database.default')));
    $this->db->setAsGlobal();
    $this->db->bootEloquent();

    Artisan::call('migrate:refresh');
    if (Schema::hasTable('jobs')) {
        DB::table('jobs')->truncate();
    }
    if (Schema::hasTable('failed_jobs')) {
        DB::table('failed_jobs')->truncate();
    }
    if (Schema::hasTable('logiaudit_logs')) {
        DB::table('logiaudit_logs')->truncate();
    }
    dump('✅ Database migrations completed in PostgreSQL...');
    dump('✅ Queue Driver: '.config('queue.default'));

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
            'via' => LogiAuditHandler::class,
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
        'delete_after_days' => 5,
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
        'delete_after_days' => 25,
    ]);

    Log::channel('logiaudit')->warning('null log context', [
        'trace_id' => 'trace-5',
        'model_id' => 125,
        'model_type' => 'App\Models\User',
        'deletable' => false,
    ]);

    dump('✅ Log messages sent!');

    $queuedJobs = DB::table('jobs')->get();
    dump('🔍 Queued Jobs in database:', json_encode($queuedJobs, JSON_PRETTY_PRINT));
    expect($queuedJobs)->toHaveCount(4);

    $queueName = config('logiaudit.log_queue_name', 'logiaudit');
    Artisan::call("queue:work --queue={$queueName} --tries=3 --stop-when-empty");

    $remainingJobs = DB::table('jobs')->get();
    dump('🔍 Remaining Jobs in Queue (After Processing):', json_encode($remainingJobs, JSON_PRETTY_PRINT));
    expect($remainingJobs)->toBeEmpty();

    $failedJobs = DB::table('failed_jobs')->get();
    dump('❌ Failed Jobs:', json_encode($failedJobs, JSON_PRETTY_PRINT));
    expect($failedJobs)->toHaveCount(1);

    $logs = LogiAuditLog::all();
    dump('✅ Log records in database:', json_encode($logs, JSON_PRETTY_PRINT));
    expect($logs)->toHaveCount(3);

    $log1 = LogiAuditLog::where('trace_id', 'trace-1')->first();

    expect($log1)->not->toBeNull()
        ->and($log1->level)->toBe('info')
        ->and($log1->message)->toBe('First log message')
        ->and($log1->trace_id)->toBe('trace-1')
        ->and($log1->model_id)->toBe(100)
        ->and($log1->model_type)->toBe('App\Models\User')
        ->and($log1->context)->toBeArray()
        ->and($log1->context)->toHaveKey('foo')
        ->and($log1->context['foo'])->toBe('bar')
        ->and($log1->deletable)->toBeTrue();

    $log2 = LogiAuditLog::where('trace_id', 'trace-2')->first();

    expect($log2)->not->toBeNull()
        ->and($log2->level)->toBe('warning')
        ->and($log2->message)->toBe('Second log message')
        ->and($log2->trace_id)->toBe('trace-2')
        ->and($log2->model_id)->toBe(200)
        ->and($log2->model_type)->toBe('App\Models\Order')
        ->and($log2->context)->toBeArray()
        ->and($log2->context)->toHaveKey('action')
        ->and($log2->context['action'])->toBe('update')
        ->and($log2->deletable)->toBeFalse();
});
