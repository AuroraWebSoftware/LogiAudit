# LogiAudit

LogiAudit is a Laravel package designed for structured logging with support for job-based log storage, pruning, and
customizable log levels.

## Features

- **Queue-Based Logging**: Logs are stored asynchronously using Laravel jobs.
- **Contextual Logging**: Supports logging with model associations, trace IDs, and additional metadata.
- **Automatic Pruning**: Logs marked as deletable can be automatically removed.
- **Monolog Integration**: Works seamlessly with Laravel's logging system.
- **IP Tracking**: Logs IP addresses for traceability.
- **Configurable Cleanup**: Define log retention periods.

## Installation

You can install the package via Composer:

```bash
composer require aurorawebsoftware/logiaudit
```

### Running Migrations

After installation, run the migration command to create the necessary database table:

```bash
php artisan migrate
```

# Log Usage

### Logging Events

You can log messages using the provided `addLog` helper function:

```php
addLog('info', 'User logged in', [
    'model_id' => $user->id,
    'model_type' => get_class($user),
    'trace_id' => Str::uuid(),
    'context' => ['role' => 'admin'],
    'ip_address' => request()->ip(),
    'deletable' => true,
    'delete_after_days' => 30,
]);
```

#### `addLog` Helper Function Details

The `addLog` function allows for flexible logging with optional parameters:

- **`level` (string, required)**: Log level (e.g., `info`, `error`, `warning`).
- **`message` (string, required)**: The log message.
- **`options` (array, optional)**: Additional context for the log entry.
    - `model_id` (int, nullable): The ID of the related model (if applicable).
    - `model_type` (string, nullable): The model's class name.
    - `trace_id` (string, nullable): A unique identifier for tracing logs across multiple services.
    - `context` (array, nullable): Any extra contextual data.
    - `ip_address` (string, nullable): The IP address of the request.
    - `deletable` (bool, default: `true`): Determines if the log can be pruned.
    - `delete_after_days` (int, nullable): Number of days before the log should be automatically deleted (if `deletable`
      is `true`).

### Using LogiAudit with Laravel's Logging System

You can also log messages using Laravel's built-in logging channels:

```php
use Illuminate\Support\Facades\Log;

Log::channel('logiaudit')->info('Custom log message', [
    'model_id' => 1,
    'model_type' => 'User',
    'trace_id' => Str::uuid(),
    'context' => ['key' => 'value'],
    'ip_address' => request()->ip(),
]);
```

To configure Laravel to use this handler, update `config/logging.php`:

```php
'channels' => [
    'logiaudit' => [
        'driver' => 'custom',
        'via' => AuroraWebSoftware\LogiAudit\Logging\LogiAuditHandler::class,
    ],
],
```

## Running the Log Pruning Command

To remove logs marked as `deletable`, run the following command:

```bash
php artisan logs:prune
```

Alternatively, you can schedule this command in your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('logs:prune')->daily();
}
```

# History Usage

History Log is simple to use. When you call **HistoryableTrait** into your model classes whose history you want to
monitor, History Log will start to keep history for your model.

```php
use LogiAuditTrait;
```

If you want to exclude some columns from this, add this variable to your model class globally and write the column names
as an array.

```php
protected $excludedColumns = ['deleted_at', 'id'];
```

If you don't want to keep history in some model events, add the following variable. Currently, this version only keeps
the history of create, update and delete events.

```php
protected $excludedEvents = ['delete', 'create'];
```

| Id | action  | table  | model            | model_id | column                                     | old_value                                                  | new_value                                                   | user_id | ip_address |
|----|---------|--------|------------------|----------|--------------------------------------------|------------------------------------------------------------|-------------------------------------------------------------|---------|------------|
| 1  | created | orders | App\Models\Order | 5        | [["order_code"],["price"],["total_price"]] | [{"order_code":"ABC"},{"price":"20"},{"total_price":"20"}] | [{"order_code":"ABCD"},{"price":"30"},{"total_price":"60"}] | 2       | 177.77.0.1 |

## Running the History Pruning Command

To delete old history records based on their `created_at` timestamp, you can run the following Artisan command:

```bash
php artisan history:prune {days}
```

Replace {days} with the number of days you want to retain.
For example, to delete all history records older than 30 days:

```bash
php artisan history:prune 30
```

# Customizing Queue Names

By default, log and history jobs are queued using the `logiaudit` queue.

You can customize the queue names by editing your config or `.env` file:

## Configuration File Setup

```php
// config/logiaudit.php
return [
    'log_queue_name' => env('LOGIAUDIT_LOG_QUEUE_NAME', 'logiaudit'),
    'history_queue_name' => env('LOGIAUDIT_HISTORY_QUEUE_NAME', 'logiaudit'),
];
```
If there is no config you can publish
```php
php artisan vendor:publish --tag=logiaudit-config
```

## Environment File Setup

For example, to change the queue name for logs and history, add the following to your `.env` file:

```env
LOGIAUDIT_LOG_QUEUE_NAME=my_custom_log_queue
LOGIAUDIT_HISTORY_QUEUE_NAME=my_custom_history_queue
```

## Important Note

If you change the queue names, make sure to run dedicated workers for the new queue names:

```bash
php artisan queue:work --queue=my_custom_log_queue
php artisan queue:work --queue=my_custom_history_queue
```

You can keep the workers running in the background using **supervisor** or **systemd**.

## License

The LogiAudit package is open-sourced software licensed under the MIT License.


