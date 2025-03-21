# LogiAudit

LogiAudit is a Laravel package designed for structured logging with support for job-based log storage, pruning, and customizable log levels.

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

## Usage

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
    - `delete_after_days` (int, nullable): Number of days before the log should be automatically deleted (if `deletable` is `true`).

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

## Running the Log Queue Worker

Since logs are queued using `onQueue('logiaudit')`, you need to run a dedicated queue worker:

```bash
php artisan queue:work --queue=logiaudit
```

To run the queue worker in the background and ensure it stays active, consider using `supervisor` or `systemd`.

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

## License

The LogiAudit package is open-sourced software licensed under the MIT License.


