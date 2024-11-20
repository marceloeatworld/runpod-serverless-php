<?php
namespace MarceloEatWorld\RunPod\Requests;

use MarceloEatWorld\RunPod\Data\RunPodResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class RunRequest extends Request implements HasBody
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
        return $this->endpointId . '/run';
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