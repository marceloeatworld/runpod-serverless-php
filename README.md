# RunPod Serverless PHP Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/marceloeatworld/runpod-serverless-php.svg)](https://packagist.org/packages/marceloeatworld/runpod-serverless-php)
[![PHP Version](https://img.shields.io/packagist/php-v/marceloeatworld/runpod-serverless-php.svg)](https://packagist.org/packages/marceloeatworld/runpod-serverless-php)
[![License](https://img.shields.io/packagist/l/marceloeatworld/runpod-serverless-php.svg)](https://packagist.org/packages/marceloeatworld/runpod-serverless-php)

A PHP SDK for the [RunPod Serverless API](https://docs.runpod.io/serverless/overview) built with [Saloon v4](https://docs.saloon.dev).

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Job Lifecycle](#job-lifecycle)
- [API Reference](#api-reference)
  - [Submitting Jobs](#submitting-jobs)
  - [Checking Job Status](#checking-job-status)
  - [Streaming Results](#streaming-results)
  - [Cancelling Jobs](#cancelling-jobs)
  - [Retrying Failed Jobs](#retrying-failed-jobs)
  - [Endpoint Health](#endpoint-health)
  - [Purging the Queue](#purging-the-queue)
- [RunPodResponse](#runpodresponse)
  - [Status Checks](#status-checks)
  - [Data Accessors](#data-accessors)
  - [JSON Serialization](#json-serialization)
- [Advanced Configuration](#advanced-configuration)
  - [Webhooks](#webhooks)
  - [Execution Policies](#execution-policies)
  - [S3 Storage Integration](#s3-storage-integration)
  - [Combining Options](#combining-options)
- [Error Handling](#error-handling)
- [Rate Limits](#rate-limits)
- [Result Retention](#result-retention)
- [Laravel Integration](#laravel-integration)
- [Support & Security](#support--security)
- [License](#license)

---

## Requirements

- **PHP 8.2** or higher
- **Composer**

Saloon v4 is installed automatically as a dependency.

## Installation

```bash
composer require marceloeatworld/runpod-serverless-php
```

## Quick Start

```php
use MarceloEatWorld\RunPod\RunPod;

// 1. Create the client with your API key
$runpod = new RunPod('your-api-key');

// 2. Target a specific endpoint
$endpoint = $runpod->endpoint('your-endpoint-id');

// 3. Submit a job
$result = $endpoint->run(['prompt' => 'A beautiful landscape']);

echo "Job submitted: " . $result->id; // e.g. "cb68890e-436f-4234-..."
echo "Status: " . $result->status;     // "IN_QUEUE"
```

> Your API key is available at [runpod.io/console/user/settings](https://www.runpod.io/console/user/settings).
> Your endpoint ID is the alphanumeric string visible in your endpoint's URL on the RunPod dashboard.

---

## Job Lifecycle

Every RunPod job goes through a state machine:

```
                          +---> COMPLETED
                          |
IN_QUEUE ---> IN_PROGRESS +---> FAILED
   |              |       |
   |              |       +---> TIMED_OUT (executionTimeout exceeded)
   |              |
   +---> TIMED_OUT (TTL expired before pickup)
   |
   +---> CANCELLED (manual cancel)
```

| Status | Description |
|---|---|
| `IN_QUEUE` | Waiting for an available worker |
| `IN_PROGRESS` | Actively being processed |
| `COMPLETED` | Finished successfully, output available |
| `FAILED` | Worker returned an error |
| `CANCELLED` | Manually stopped via `cancel()` |
| `TIMED_OUT` | Expired (TTL in queue or executionTimeout during processing) |

---

## API Reference

### Submitting Jobs

#### Async: `run(array $input)`

Submits a job and returns immediately. You then poll `status()` or use a webhook.

```php
$result = $endpoint->run(['prompt' => 'A futuristic city']);

echo $result->id;     // "cb68890e-436f-4234-..."
echo $result->status; // "IN_QUEUE"
```

**Payload limit:** 10 MB.

#### Sync: `runSync(array $input)`

Submits a job and waits for completion. Best for fast tasks (< 90 seconds).

```php
$result = $endpoint->runSync(['prompt' => 'Hello world']);

if ($result->isCompleted()) {
    $output = $result->getOutput();
}
```

> If the job takes longer than ~90 seconds, `runSync` returns with status `IN_PROGRESS`.
> You must then fall back to polling `status()`.

**Payload limit:** 20 MB.

#### Full Polling Example

```php
$result = $endpoint->run(['prompt' => 'Generate something']);

// Poll until terminal state
while ($result->isInQueue() || $result->isInProgress()) {
    sleep(2); // Wait 2 seconds between polls
    $result = $endpoint->status($result->id);
}

// Handle terminal states
if ($result->isCompleted()) {
    $output = $result->getOutput();
    echo "Done! Worker: " . $result->getWorkerId();
    echo "Execution time: " . $result->getExecutionTime() . " ms";
    echo "Queue delay: " . $result->getDelayTime() . " ms";
} elseif ($result->isFailed()) {
    echo "Error: " . $result->getError();
} elseif ($result->isTimedOut()) {
    echo "Timed out, retrying...";
    $result = $endpoint->retry($result->id);
} elseif ($result->isCancelled()) {
    echo "Job was cancelled";
}
```

---

### Checking Job Status

#### `status(string $jobId)`

Retrieve the current state and results of a job.

```php
$status = $endpoint->status('cb68890e-436f-4234-...');

echo $status->status;            // "COMPLETED"
echo $status->getOutput();       // The worker's output
echo $status->getExecutionTime(); // 2297 (ms)
echo $status->getDelayTime();    // 2188 (ms)
echo $status->getWorkerId();     // "smjcwth8e5sqvv"
```

---

### Streaming Results

#### `stream(string $jobId)`

Retrieve incremental results from a streaming job. The worker must support streaming.

```php
$result = $endpoint->run(['prompt' => 'Write a story']);

// Wait a bit for the worker to start producing chunks
sleep(5);

$stream = $endpoint->stream($result->id);
$chunks = $stream->getStream(); // Array of stream chunks

foreach ($chunks as $chunk) {
    echo $chunk['output'];
}
```

> Streaming in RunPod is **poll-based**, not chunked transfer encoding.
> Each chunk is limited to 1 MB.

---

### Cancelling Jobs

#### `cancel(string $jobId)`

Cancel a queued or running job.

```php
$result = $endpoint->run(['prompt' => 'Something expensive']);

// Changed my mind
$cancelled = $endpoint->cancel($result->id);
```

---

### Retrying Failed Jobs

#### `retry(string $jobId)`

Requeue a failed or timed-out job. RunPod re-uses the same job ID and original input.

```php
$status = $endpoint->status($jobId);

if ($status->isFailed() || $status->isTimedOut()) {
    $retry = $endpoint->retry($jobId);
    echo "Retrying: " . $retry->id;     // Same job ID
    echo "Status: " . $retry->status;   // "IN_QUEUE"
}
```

---

### Endpoint Health

#### `health()`

Get worker pool and job pipeline statistics.

```php
$health = $endpoint->health();

// The raw data contains:
// {
//   "jobs": { "completed": 367, "failed": 6, "inProgress": 0, "inQueue": 0, "retried": 0 },
//   "workers": { "idle": 1, "initializing": 0, "ready": 1, "running": 0, "throttled": 0, "unhealthy": 0 }
// }

$data = $health->data;
echo "Workers ready: " . $data['workers']['ready'];
echo "Jobs in queue: " . $data['jobs']['inQueue'];
echo "Jobs failed: " . $data['jobs']['failed'];
```

---

### Purging the Queue

#### `purgeQueue()`

Remove all pending jobs from the queue. Running jobs are not affected.

```php
$endpoint->purgeQueue();
```

> Use with caution. This is irreversible and has a strict rate limit (2 calls per 10 seconds).

---

## RunPodResponse

Every method returns a `RunPodResponse` object wrapping the raw API JSON response.

### Status Checks

```php
$response->isCompleted();  // COMPLETED
$response->isInQueue();    // IN_QUEUE
$response->isInProgress(); // IN_PROGRESS
$response->isFailed();     // FAILED
$response->isCancelled();  // CANCELLED
$response->isTimedOut();   // TIMED_OUT
```

### Data Accessors

| Method | Return Type | Description |
|---|---|---|
| `$response->id` | `?string` | Unique job identifier |
| `$response->status` | `?string` | Current job status |
| `$response->data` | `array` | Complete raw API response |
| `->getOutput()` | `mixed` | Worker's output (when `COMPLETED`) |
| `->getError()` | `mixed` | Error details (when `FAILED`) |
| `->getMetrics()` | `?array` | Execution metrics |
| `->getExecutionTime()` | `?int` | Active processing time in ms |
| `->getDelayTime()` | `?int` | Time spent waiting in queue in ms |
| `->getWorkerId()` | `?string` | ID of the worker that processed the job |
| `->getStream()` | `?array` | Array of stream chunks (from `stream()`) |

### JSON Serialization

`RunPodResponse` implements `JsonSerializable`, so you can pass it directly to `json_encode()` or return it from a Laravel controller:

```php
// Plain PHP
echo json_encode($response);

// Laravel
return response()->json($response);
```

---

## Advanced Configuration

The fluent methods `withWebhook()`, `withPolicy()`, and `withS3Config()` configure options on the endpoint resource. They are chainable and apply to the next `run()` or `runSync()` call.

> **Note:** These options are **sticky** on the resource instance. If you call `withWebhook()` once, subsequent `run()` calls on the same instance will continue sending that webhook. Create a new endpoint instance if you need different config.

### Webhooks

Instead of polling `status()`, you can provide a webhook URL. RunPod will POST the complete response JSON to your URL when the job finishes.

```php
$result = $endpoint
    ->withWebhook('https://your-site.com/api/runpod/callback')
    ->run(['prompt' => 'Your prompt']);

// No need to poll - RunPod will call your webhook
echo "Job submitted: " . $result->id;
```

Webhook behavior:
- RunPod POSTs the full response JSON on completion
- Your endpoint must return HTTP 200
- On failure, RunPod retries **2 more times** with a **10 second delay** between retries

### Execution Policies

Control job timeout and priority behavior.

```php
$result = $endpoint
    ->withPolicy([
        'executionTimeout' => 900000,  // 15 min - max active runtime (ms)
        'lowPriority' => false,        // true = won't trigger worker autoscaling
        'ttl' => 3600000,              // 1 hour - total job lifespan from submission (ms)
    ])
    ->run(['prompt' => 'Your prompt']);
```

| Parameter | Default | Range | Description |
|---|---|---|---|
| `executionTimeout` | 600,000 (10 min) | 5s - 7 days | Max time a job can actively run on a worker |
| `ttl` | 86,400,000 (24h) | 10s - 7 days | Total lifespan from submission (includes queue wait) |
| `lowPriority` | `false` | - | If `true`, the job won't trigger autoscaling of new workers |

> **`executionTimeout` vs `ttl`:** TTL counts from when the job is *submitted* (including queue time). executionTimeout counts from when a worker *starts processing* the job. If TTL expires while a job is running, it's immediately removed.

### S3 Storage Integration

For large payloads exceeding the 10/20 MB limits, use S3 integration to pass data via object storage.

```php
$result = $endpoint
    ->withS3Config([
        'accessId' => 'your-access-key-id',
        'accessSecret' => 'your-secret-access-key',
        'bucketName' => 'your-bucket-name',
        'endpointUrl' => 'https://your-s3-endpoint.com',
    ])
    ->run(['prompt' => 'Your prompt']);
```

### Combining Options

All fluent methods are chainable:

```php
$result = $endpoint
    ->withWebhook('https://your-site.com/callback')
    ->withPolicy(['executionTimeout' => 120000, 'ttl' => 600000])
    ->withS3Config(['accessId' => '...', 'accessSecret' => '...', 'bucketName' => '...', 'endpointUrl' => '...'])
    ->run(['prompt' => 'Your prompt']);
```

---

## Error Handling

This client uses Saloon's `AlwaysThrowOnErrors` trait. Any HTTP 4xx/5xx response automatically throws an exception. Connection-level errors (DNS, timeout) are also thrown.

```php
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\ServerException;

try {
    $result = $endpoint->run(['prompt' => 'test']);
} catch (FatalRequestException $e) {
    // Connection-level errors: DNS failure, TLS error, timeout
    echo "Connection failed: " . $e->getMessage();
} catch (ClientException $e) {
    // 4xx errors
    $status = $e->getResponse()->status();
    match ($status) {
        401 => 'Invalid API key',
        404 => 'Endpoint not found or job TTL expired',
        429 => 'Rate limit exceeded - implement backoff',
        default => 'Client error: ' . $status,
    };
} catch (ServerException $e) {
    // 5xx errors
    echo "RunPod server error: " . $e->getResponse()->status();
} catch (RequestException $e) {
    // Catch-all for any other HTTP error
    echo "Request failed: " . $e->getResponse()->status();
}
```

Exception hierarchy:

```
SaloonException
  FatalRequestException        (connection errors - always thrown)
  RequestException             (HTTP errors)
    ServerException (5xx)
      InternalServerErrorException (500)
      ServiceUnavailableException  (503)
      GatewayTimeoutException      (504)
    ClientException (4xx)
      UnauthorizedException        (401)
      ForbiddenException           (403)
      NotFoundException            (404)
      UnprocessableEntityException (422)
      TooManyRequestsException     (429)
```

---

## Rate Limits

RunPod enforces per-endpoint rate limits:

| Endpoint | Max per 10s | Max Concurrent |
|---|---|---|
| `/run` | 1,000 | 200 |
| `/runsync` | 2,000 | 400 |
| `/status` | 2,000 | 400 |
| `/stream` | 2,000 | 400 |
| `/cancel` | 100 | 20 |
| `/purge-queue` | 2 | - |

Exceeding these limits returns HTTP 429. Implement exponential backoff with jitter when retrying.

---

## Result Retention

RunPod automatically deletes job results after a retention period:

| Mode | Retention After Completion |
|---|---|
| Async (`run`) | **30 minutes** |
| Sync (`runSync`) | **1 minute** (5 minutes max) |

Fetch your results within these windows, or use webhooks to receive results immediately.

---

## Laravel Integration

### 1. Configuration

Add to `config/services.php`:

```php
'runpod' => [
    'api_key' => env('RUNPOD_API_KEY'),
],
```

Add to your `.env`:

```
RUNPOD_API_KEY=your-api-key-here
```

### 2. Service Provider

Register as a singleton in `AppServiceProvider` (or a dedicated provider):

```php
use MarceloEatWorld\RunPod\RunPod;

public function register(): void
{
    $this->app->singleton(RunPod::class, function () {
        return new RunPod(config('services.runpod.api_key'));
    });
}
```

### 3. Usage in Controllers

```php
use MarceloEatWorld\RunPod\RunPod;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function generate(RunPod $runpod, Request $request)
    {
        $endpoint = $runpod->endpoint('your-endpoint-id');
        $result = $endpoint->run($request->validated());

        return response()->json([
            'job_id' => $result->id,
            'status' => $result->status,
        ]);
    }

    public function status(RunPod $runpod, string $jobId)
    {
        $endpoint = $runpod->endpoint('your-endpoint-id');
        $status = $endpoint->status($jobId);

        return response()->json($status); // Uses JsonSerializable
    }
}
```

### 4. Usage in Jobs / Queues

```php
use MarceloEatWorld\RunPod\RunPod;

class ProcessAITask implements ShouldQueue
{
    public function __construct(
        private string $endpointId,
        private array $input,
    ) {}

    public function handle(RunPod $runpod): void
    {
        $endpoint = $runpod->endpoint($this->endpointId);
        $result = $endpoint->runSync($this->input);

        if ($result->isCompleted()) {
            // Store output...
        }
    }
}
```

---

## Support & Security

For security issues, please email [diagngo@gmail.com](mailto:diagngo@gmail.com).

## License

MIT License - see [LICENSE](LICENSE)
