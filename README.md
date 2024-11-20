# RunPod Serverless PHP Client
A lightweight PHP client for RunPod Serverless API built with Saloon v3.

## Features
* 🚀 Support for all RunPod Serverless endpoints
* 🔄 Async and sync job execution with status tracking
* 📡 Webhook support
* ⚙️ Execution policy control
* 📦 S3 storage integration
* ⚡ Simple, intuitive API

## Installation
```bash
composer require marceloeatworld/runpod-serverless-php
```

## Quick Start
```php
use MarceloEatWorld\RunPod\RunPod;

$runpod = new RunPod('your-api-key');
$endpoint = $runpod->endpoint('your-endpoint-id');

// Async run
$result = $endpoint->run([
    'prompt' => 'A beautiful landscape'
]);

// Check status
if ($result->isInQueue()) {
    echo "Job queued with ID: " . $result->id;
}

// Get status later
$status = $endpoint->status($result->id);

if ($status->isCompleted()) {
    $output = $status->getOutput();
    echo "Job completed!";
} elseif ($status->isFailed()) {
    $error = $status->getError();
    echo "Job failed: " . $error;
}
```

## Response Handling
The `RunPodResponse` provides:
```php
// Status checks
$response->isCompleted();  // true if status is COMPLETED
$response->isInQueue();    // true if status is IN_QUEUE
$response->isInProgress(); // true if status is IN_PROGRESS
$response->isFailed();     // true if status is FAILED
$response->isCancelled();  // true if status is CANCELLED

// Data access
$response->id;             // Job ID
$response->status;         // Status string
$response->data;           // Complete response data

// Helper methods
$response->getOutput();        // Get job output data
$response->getError();         // Get error details
$response->getMetrics();       // Get execution metrics
$response->getExecutionTime(); // Get execution time in ms
$response->getDelayTime();     // Get delay time in ms
```

## Advanced Usage
### Using Webhooks
```php
$result = $endpoint
    ->withWebhook('https://your-site.com/webhook')
    ->run([
        'prompt' => 'Your prompt'
    ]);
```

### Execution Policies
```php
$result = $endpoint
    ->withPolicy([
        'executionTimeout' => 60000,
        'lowPriority' => false,
        'ttl' => 3600000
    ])
    ->run([
        'prompt' => 'Your prompt'
    ]);
```

### S3 Integration
```php
$result = $endpoint
    ->withS3Config([
        'accessId' => 'your-access-id',
        'accessSecret' => 'your-access-secret',
        'bucketName' => 'your-bucket',
        'endpointUrl' => 'your-endpoint-url'
    ])
    ->run([
        'prompt' => 'Your prompt'
    ]);
```

## All Available Methods
```php
// Run endpoints
$result = $endpoint->run();      // Async execution
$result = $endpoint->runSync();  // Sync execution

// Status and control
$status = $endpoint->status($jobId);   // Get job status
$health = $endpoint->health();         // Check endpoint health
$cancel = $endpoint->cancel($jobId);   // Cancel a job
$purge = $endpoint->purgeQueue();      // Purge endpoint queue
$stream = $endpoint->stream($jobId);   // Stream job results
```

## Laravel Integration
Add to `config/services.php`:
```php
'runpod' => [
    'api_key' => env('RUNPOD_API_KEY'),
],
```

Register in a service provider:
```php
public function register()
{
    $this->app->singleton(RunPod::class, function () {
        return new RunPod(config('services.runpod.api_key'));
    });
}
```

Use in controllers:
```php
use MarceloEatWorld\RunPod\RunPod;

class AIController extends Controller
{
    public function generate(RunPod $runpod, Request $request)
    {
        $endpoint = $runpod->endpoint('your-endpoint-id');
        
        $result = $endpoint->run($request->all());
        
        return response()->json([
            'job_id' => $result->id,
            'status' => $result->status
        ]);
    }
}
```

## Support & Security
For security issues, please email [diagngo@gmail.com](mailto:diagngo@gmail.com).

## License
MIT License - see LICENSE