<?php

namespace MarceloEatWorld\RunPod\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class StreamRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $endpointId,
        protected string $jobId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "{$this->endpointId}/stream/{$this->jobId}";
    }
}