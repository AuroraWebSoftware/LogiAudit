<?php

namespace AuroraWebSoftware\LogiAudit\Tests\Jobs;

use AuroraWebSoftware\LogiAudit\Tests\Models\LogiAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class StoreLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $level;

    public string $message;

    public ?int $modelId;

    public ?string $modelType;

    public ?string $traceId;

    public ?array $context;

    public ?string $ipAddress;

    public bool $deletable;

    public ?int $deleteAfterDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $level,
        string $message,
        ?int $modelId = null,
        ?string $modelType = null,
        ?string $traceId = null,
        ?array $context = null,
        ?string $ipAddress = null,
        bool $deletable = true,
        ?int $deleteAfterDays = null
    ) {
        $this->level = $level;
        $this->message = $message;
        $this->modelId = $modelId;
        $this->modelType = $modelType;
        $this->traceId = $traceId;
        $this->context = $context;
        $this->ipAddress = $ipAddress;
        $this->deletable = $deletable;
        $this->deleteAfterDays = $deleteAfterDays;

        $this->onQueue('logiaudit');

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        dump('🚀 StoreLogJob handle() triggered!');
        if ($this->level === 'critical') {
            $this->fail(new \Exception("Simulated failure for 'critical' level logs!"));

            return;
        }

        if (empty($this->message)) {
            $this->fail('Job failed: Log message is empty.');

            return;
        }

        LogiAuditLog::create([
            'level' => $this->level,
            'message' => $this->message,
            'model_id' => $this->modelId,
            'model_type' => $this->modelType,
            'trace_id' => $this->traceId,
            'context' => $this->context,
            'ip_address' => $this->ipAddress,
            'deletable' => $this->deletable,
            'deleted_at' => ($this->deletable && $this->deleteAfterDays)
                ? Carbon::now()->addDays($this->deleteAfterDays)
                : null,
        ]);

        dump('✅ Log entry created in database!');
    }
}
