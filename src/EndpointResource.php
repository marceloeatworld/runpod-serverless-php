<?php  
namespace MarceloEatWorld\RunPod;

use MarceloEatWorld\RunPod\Data\RunPodResponse;
use MarceloEatWorld\RunPod\Requests\RunRequest;
use MarceloEatWorld\RunPod\Requests\RunSyncRequest;
use MarceloEatWorld\RunPod\Requests\StatusRequest;
use MarceloEatWorld\RunPod\Requests\CancelRequest;
use MarceloEatWorld\RunPod\Requests\HealthRequest;
use MarceloEatWorld\RunPod\Requests\PurgeQueueRequest;
use MarceloEatWorld\RunPod\Requests\StreamRequest;

class EndpointResource extends Resource
{
    protected ?string $webhookUrl = null;
    protected ?array $policy = null;
    protected ?array $s3Config = null;

    public function run(array $input): RunPodResponse
    {
        $request = new RunRequest($this->endpointId, $input, $this->webhookUrl, $this->policy, $this->s3Config);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function runSync(array $input): RunPodResponse
    {
        $request = new RunSyncRequest($this->endpointId, $input, $this->webhookUrl, $this->policy, $this->s3Config);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function status(string $jobId): RunPodResponse
    {
        $request = new StatusRequest($this->endpointId, $jobId);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function cancel(string $jobId): RunPodResponse
    {
        $request = new CancelRequest($this->endpointId, $jobId);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function health(): RunPodResponse
    {
        $request = new HealthRequest($this->endpointId);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function purgeQueue(): RunPodResponse
    {
        $request = new PurgeQueueRequest($this->endpointId);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function stream(string $jobId): RunPodResponse
    {
        $request = new StreamRequest($this->endpointId, $jobId);
        $response = $this->connector->send($request);
        return RunPodResponse::fromResponse($response);
    }

    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;
        return $this;
    }

    public function withPolicy(array $policy): self
    {
        $this->policy = $policy;
        return $this;
    }

    public function withS3Config(array $config): self
    {
        $this->s3Config = $config;
        return $this;
    }
}
