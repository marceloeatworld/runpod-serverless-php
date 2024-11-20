<?php

namespace MarceloEatWorld\RunPod\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class PurgeQueueRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $endpointId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "{$this->endpointId}/purge-queue";
    }
}
