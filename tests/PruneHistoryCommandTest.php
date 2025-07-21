<?php

use AuroraWebSoftware\LogiAudit\Jobs\PruneHistoryJob;
use AuroraWebSoftware\LogiAudit\Models\LogiAuditHistory;
use AuroraWebSoftware\LogiAudit\Tests\Models\TestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['queue.default' => 'database']);
    Artisan::call('migrate:fresh');

    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->nullable();
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

    dump('✅ Environment ready.');
});

it('deletes old history records via history:prune command', function () {
    $recent = TestModel::create(['name' => 'Recent']);
    $recent->update(['status' => 'updated']);
    $recent->delete();

    $old = TestModel::create(['name' => 'Old']);
    $oldId = $old->id;
    $old->update(['status' => 'outdated']);
    $old->delete();

    Artisan::call('queue:work', [
        '--queue' => 'logiaudit',
        '--tries' => 1,
        '--stop-when-empty' => true,
    ]);

    $oldHistoryIds = LogiAuditHistory::where('model_id', $oldId)->pluck('id');
    DB::table('logiaudit_history')
        ->whereIn('id', $oldHistoryIds)
        ->update([
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

    $before = LogiAuditHistory::count();
    dump("📊 Before prune: $before records");

    Artisan::call('history:prune', ['days' => 10]);

    $after = LogiAuditHistory::count();
    dump("📉 After prune: $after records");

    $deleted = $before - $after;
    dump("🗑 Deleted history entries: $deleted");

    expect($after)->toBeLessThan($before);
    expect(LogiAuditHistory::where('model_id', $oldId)->count())->toBe(0);
});

