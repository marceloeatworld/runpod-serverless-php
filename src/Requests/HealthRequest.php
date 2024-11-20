<?php
namespace MarceloEatWorld\RunPod\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class HealthRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $endpointId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "{$this->endpointId}/health";
    }
}
