<?php

namespace MarceloEatWorld\RunPod;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use GuzzleHttp\RequestOptions;

class RunPod extends Connector
{
    use AcceptsJson;

    public function __construct(public readonly string $apiKey) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.runpod.ai/v2/';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            RequestOptions::TIMEOUT => 300, // 5 minutes timeout
            RequestOptions::CONNECT_TIMEOUT => 30, // 30 seconds connection timeout
        ];
    }

    public function endpoint(string $endpointId): EndpointResource
    {
        return new EndpointResource($this, $endpointId);
    }
}