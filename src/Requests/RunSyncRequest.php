<?php
namespace MarceloEatWorld\RunPod\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class RunSyncRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $endpointId,
        protected array $input,
        protected ?string $webhookUrl = null,
        protected ?array $policy = null,
        protected ?array $s3Config = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->endpointId . '/runsync';
    }

    protected function defaultBody(): array
    {

        $body = ['input' => $this->input];
        
        if ($this->webhookUrl) {
            $body['webhook'] = $this->webhookUrl;
        }
        
        if ($this->policy) {
            $body['policy'] = $this->policy;
        }
        
        if ($this->s3Config) {
            $body['s3Config'] = $this->s3Config;
        }
        
        return $body;
    }
}