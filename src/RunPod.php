<?php

namespace MarceloEatWorld\RunPod;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

class RunPod extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasTimeout;

    protected int $connectTimeout = 30;
    protected int $requestTimeout = 300;

    public function __construct(public readonly string $apiKey) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.runpod.ai/v2/';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiKey);
    }

    public function endpoint(string $endpointId): EndpointResource
    {
        return new EndpointResource($this, $endpointId);
    }
}
