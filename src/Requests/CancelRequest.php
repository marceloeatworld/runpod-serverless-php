<?php
namespace MarceloEatWorld\RunPod\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CancelRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $endpointId,
        protected string $jobId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "{$this->endpointId}/cancel/{$this->jobId}";
    }
}