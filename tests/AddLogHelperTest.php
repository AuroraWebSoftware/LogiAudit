<?php

declare(strict_types=1);

use AuroraWebSoftware\LogiAudit\Tests\Models\LogiAuditLog;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['queue.default' => 'database']);

    Artisan::call('migrate:refresh');

    dump('✅ Database migrations completed in PostgreSQL...');
    dump('✅ Queue Driver:', config('queue.default'));

    $this->db = new DB;
    $this->db->addConnection(config('database.connections.pgsql'));
    $this->db->setAsGlobal();
    $this->db->bootEloquent();

    if (! Schema::hasTable('jobs')) {
        Artisan::call('queue:table');
        Artisan::call('migrate');
        dump("✅ 'jobs' table created in PostgreSQL for testing...");
    }

    if (! Schema::hasTable('failed_jobs')) {
        Artisan::call('queue:failed-table');
        Artisan::call('migrate');
        dump("✅ 'failed_jobs' table created in PostgreSQL for testing...");
    }

    Config::set('logiaudit.queue', 'logiaudit');
});

it('dispatches and processes multiple StoreLogJob entries with delays and failures', function () {
    dump('✅ Queue Driver:', config('queue.default'));

    addLogT('error', 'Real queue test message', [
        'model_id' => 123,
        'model_type' => 'App\\Models\\User',
        'trace_id' => 'real-queue-999',
        'ip_address' => '127.0.0.1',
        'deletable' => false,
    ]);

    addLogT('info', 'User logged in', [
        'model_id' => 200,
        'model_type' => 'App\\Models\\User',
        'trace_id' => 'user-login-001',
        'context' => ['action' => 'login', 'üüöö' => 'İİÖÖÜÜ'],
        'ip_address' => '192.168.1.1',
        'deletable' => true,
        'delete_after_days' => 5,
    ]);

    addLogT('warning', 'User attempted unauthorized access', [
        'model_id' => 201,
        'model_type' => 'App\\Models\\Admin',
        'trace_id' => 'unauthorized-access',
        'context' => ['section' => 'admin_panel'],
        'ip_address' => '192.168.1.2',
        'deletable' => false,
    ]);

    addLogT('critical', 'Database connection failed', [
        'model_id' => null,
        'model_type' => null,
        'trace_id' => 'db-error-500',
        'context' => ['error' => 'timeout'],
        'ip_address' => null,
        'deletable' => false,
    ]);

    addLogT('debug', 'API request received', [
        'model_id' => 300,
        'model_type' => 'App\\Models\\ApiRequest',
        'trace_id' => 'api-request-xyz',
        'context' => ['endpoint' => '/api/data'],
        'ip_address' => '10.0.0.1',
        'deletable' => true,
        'delete_after_days' => 35,
    ]);

    addLogT('error', '', [
        'model_id' => 300,
        'model_type' => 'App\\Models\\ApiRequest',
        'trace_id' => 'api-request-xyz',
        'ip_address' => '10.0.0.1',
        'deletable' => true,
        'delete_after_days' => 35,
    ]);

    addLogT('warning', 'Context null given', [
        'trace_id' => 'api-request-xyz',
        'ip_address' => '10.0.0.5',
        'deletable' => false,
        'delete_after_days' => 12,
    ]);

    $queuedJobs = DB::table('jobs')->get();
    foreach ($queuedJobs as $job) {
        dump("🔹 Job ID: {$job->id}");
        dump("🔹 Queue: {$job->queue}");
        dump("🔹 Attempts: {$job->attempts}");
        dump("🔹 Created At: {$job->created_at}");
        dump("🔹 Available At: {$job->available_at}");

        $payload = json_decode($job->payload, true);
        if ($payload) {
            dump('🔹 Payload Data:', $payload);
        } else {
            dump('⚠️ WARNING: Payload JSON could not be parsed!', $job->payload);
        }
    }

    dump('🔸 Before processing queue');

    Artisan::call('queue:work', [
        '--queue' => 'logiaudit',
        '--tries' => 1,
        '--stop-when-empty' => true,
    ]);

    dump('✅ After processing queue');

    $remainingJobs = DB::table('jobs')->get();
    dump('🔹 Jobs table after processing queue:', $remainingJobs);

    $failedJobs = DB::table('failed_jobs')->get();
    dump('❌ Failed Jobs Table:', $failedJobs);

    $logs = LogiAuditLog::all();
    dump('✅ All log records in PostgreSQL (logiaudit_logs table):', $logs);

    expect($logs)->toHaveCount(5);
    expect($failedJobs)->toHaveCount(2);
});
