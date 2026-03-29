<?php

namespace MarceloEatWorld\RunPod\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RetryRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $endpointId,
        protected string $jobId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "{$this->endpointId}/retry/{$this->jobId}";
    }
}
