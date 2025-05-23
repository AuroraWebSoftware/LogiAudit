<?php

use AuroraWebSoftware\LogiAudit\Jobs\PruneLogJob;
use AuroraWebSoftware\LogiAudit\Models\LogiAuditLog;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['queue.default' => 'database']);
    Artisan::call('migrate:refresh');
    dump('✅ Database migrations completed ...');
    dump('✅ Queue Driver: '.config('queue.default'));
    $this->db = new DB;
    $this->db->addConnection(config('database.connections.'.config('database.default')));
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
});

it('processes StoreLogJob, checks failed jobs, and prunes logs correctly', function () {
    dump('✅ Queue Driver: '.config('queue.default'));

    addLogT('error', 'Real queue test message', [
        'model_id' => 123,
        'model_type' => 'App\\Models\\User',
        'trace_id' => 'real-queue-999',
        'context' => ['foo' => 'bar', 'test_value' => 'test'],
        'ip_address' => '127.0.0.1',
        'deletable' => false,
        'delete_after_days' => 5,
    ]);

    addLogT('info', 'User logged in', [
        'model_id' => 200,
        'model_type' => 'App\\Models\\User',
        'trace_id' => 'user-login-001',
        'context' => ['action' => 'login'],
        'ip_address' => '192.168.1.1',
        'deletable' => true,
        'delete_after_days' => -2,
    ]);

    addLogT('warning', 'User attempted unauthorized access', [
        'model_id' => 201,
        'model_type' => 'App\\Models\\Admin',
        'trace_id' => 'unauthorized-access',
        'context' => ['section' => 'admin_panel'],
        'ip_address' => '192.168.1.2',
        'deletable' => false,
    ]);

    addLogT('debug', 'API request received', [
        'model_id' => 300,
        'model_type' => 'App\\Models\\ApiRequest',
        'trace_id' => 'api-request-xyz',
        'context' => ['endpoint' => '/api/data'],
        'ip_address' => '10.0.0.1',
        'deletable' => true,
        'delete_after_days' => -10,
    ]);

    LogiAuditLog::create([
        'level' => 'info',
        'message' => 'Expired log record',
        'model_id' => 400,
        'model_type' => 'App\\Models\\OldRecord',
        'trace_id' => 'expired-log-001',
        'context' => ['note' => 'expired'],
        'ip_address' => '192.168.1.3',
        'deletable' => true,
        'deleted_at' => Carbon::now()->subDays(2),
    ]);

    LogiAuditLog::create([
        'level' => 'info',
        'message' => 'Future deletable log',
        'model_id' => 500,
        'model_type' => 'App\\Models\\FutureModel',
        'trace_id' => 'future-log-001',
        'context' => ['note' => 'future'],
        'ip_address' => '192.168.1.4',
        'deletable' => true,
        'deleted_at' => Carbon::now()->addDays(15),
    ]);

    $queuedJobs = DB::table('jobs')->get();
    dump('🔍 Jobs in queue:', $queuedJobs);

    Artisan::call('queue:work --queue=logiaudit --tries=3 --stop-when-empty');
    dump('✅ After processing queue');

    $failedJobs = DB::table('failed_jobs')->get();
    dump('❌ Failed Jobs:', $failedJobs);

    $logs = LogiAuditLog::all();
    dump('✅ All log records before pruning:', $logs);
    expect($logs)->toHaveCount(6);

    Queue::fake();
    Artisan::call('logs:prune');
    expect(Artisan::output())->toContain('PruneLogJob dispatched successfully.');
    Queue::assertPushed(PruneLogJob::class);

    $initialCount = DB::table('logiaudit_logs')->count();
    dump("🔍 Initial log count: $initialCount");

    (new PruneLogJob)->handle();

    $remainingCount = DB::table('logiaudit_logs')->count();
    dump("🔍 Remaining log count after prune: $remainingCount");

    $deletedCount = $initialCount - $remainingCount;
    dump("❌ Deleted logs count: $deletedCount");

    expect($remainingCount)->toBe(3)
        ->and(DB::table('logiaudit_logs')->where('deleted_at', '>', Carbon::now())->where('deletable', true)->exists())->toBeTrue();
});
