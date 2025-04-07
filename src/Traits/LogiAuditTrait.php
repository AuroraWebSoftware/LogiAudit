<?php

namespace AuroraWebSoftware\LogiAudit\Traits;

use AuroraWebSoftware\LogiAudit\Events\HistoryEventObserver;

trait LogiAuditTrait
{
    /**
     * @return void
     */
    public static function bootLogiAuditTrait()
    {
        static::observe(new HistoryEventObserver);
    }

    /**
     * @return array|mixed|string[]
     */
    public function getExcludedColumns()
    {
        return ! isset($this->excludedColumns)
            ? []
            : $this->excludedColumns;
    }

    /**
     * @return array|mixed|string[]
     */
    public function getExcludedEvents()
    {
        return ! isset($this->excludedEvents)
            ? []
            : $this->excludedEvents;
    }
}
