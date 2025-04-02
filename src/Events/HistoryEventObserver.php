<?php

namespace AuroraWebSoftware\LogiAudit\Events;

use AuroraWebSoftware\LogiAudit\Jobs\StoreHistoryJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * @method array getExcludedEvents()
 * @method array getExcludedColumns()
 */
class HistoryEventObserver
{
    /**
     * @return bool
     */
    public function created(Model $model)
    {
        if (! in_array('create', $model->getExcludedEvents())) {
            $this->saveHistory('created', $model);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function updated(Model $model)
    {
        if (! in_array('update', $model->getExcludedEvents())) {
            if ($model->getChanges()) {
                $this->saveHistory('updated', $model);
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function deleted(Model $model)
    {
        if (! in_array('delete', $model->getExcludedEvents())) {
            $this->saveHistory('deleted', $model);
        }
    }

    /**
     * @return void
     */
    private function saveHistory($event, $model)
    {
        try {
            $dirty = $model->getDirty();

            $oldValues = [];
            $newValues = [];
            $columns = [];

            $attributes = $model->getExcludedColumns();

            foreach ($dirty as $column => $value) {
                if (! in_array($column, $attributes)) {
                    $oldValues[] = [$column => $model->getOriginal($column)];
                    $newValues[] = [$column => $value];
                    $columns[] = $column;
                }
            }

            if ((empty($columns) && $event !== 'deleted')) {
                return;
            }

            StoreHistoryJob::dispatch(
                $event,
                $model->getTable(),
                $model->getMorphClass(),
                $model->getKey(),
                $event !== 'deleted' ? $columns : null,
                $event === 'updated' ? $oldValues : null,
                $event !== 'deleted' ? $newValues : null,
                Auth::check() ? Auth::user()->id : null,
                Request::ip(),
            );
        } catch (\Exception $e) {
            Log::error('Audit history job dispatch failed: '.$e->getMessage());
        }
    }
}
