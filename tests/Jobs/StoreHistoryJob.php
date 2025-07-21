<?php

namespace AuroraWebSoftware\LogiAudit\Tests\Jobs;

use AuroraWebSoftware\LogiAudit\Models\LogiAuditHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $action;

    public string $table;

    public string $model;

    public int $modelId;

    public ?array $columns;

    public ?array $oldValues;

    public ?array $newValues;

    public ?int $userId;

    public ?string $ipAddress;

    public function __construct(
        string  $action,
        string  $table,
        string  $model,
        int     $modelId,
        ?array  $columns = null,
        ?array  $oldValues = null,
        ?array  $newValues = null,
        ?int    $userId = null,
        ?string $ipAddress = null,
    )
    {
        $this->action = $action;
        $this->table = $table;
        $this->model = $model;
        $this->modelId = $modelId;
        $this->columns = $columns;
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        $this->userId = $userId;
        $this->ipAddress = $ipAddress;

        $this->onQueue(config('logiaudit.history_queue_name'));
    }

    public function handle(): void
    {
        if ($this->action === 'fail') {
            throw new \Exception("Intentional failure triggered by action = 'fail'");
        }

        LogiAuditHistory::create([
            'action' => $this->action,
            'table' => $this->table,
            'model' => $this->model,
            'model_id' => $this->modelId,
            'column' => !empty($this->columns) ? $this->columns : null,
            'old_value' => !empty($this->oldValues) ? $this->oldValues : null,
            'new_value' => !empty($this->newValues) ? $this->newValues : null,
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
