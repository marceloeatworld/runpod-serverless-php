<?php

namespace MarceloEatWorld\RunPod\Data;

use Saloon\Http\Response;

class RunPodResponse implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly array $data,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            id: $data['id'] ?? null,
            status: $data['status'] ?? null,
            data: $data,
        );
    }

    public function isCompleted(): bool
    {
        return strtoupper((string) $this->status) === 'COMPLETED';
    }

    public function isInQueue(): bool
    {
        return strtoupper((string) $this->status) === 'IN_QUEUE';
    }

    public function isInProgress(): bool
    {
        return strtoupper((string) $this->status) === 'IN_PROGRESS';
    }

    public function isFailed(): bool
    {
        return strtoupper((string) $this->status) === 'FAILED';
    }

    public function isCancelled(): bool
    {
        return strtoupper((string) $this->status) === 'CANCELLED';
    }

    public function isTimedOut(): bool
    {
        return strtoupper((string) $this->status) === 'TIMED_OUT';
    }

    public function getOutput(): mixed
    {
        return $this->data['output'] ?? null;
    }

    public function getError(): mixed
    {
        return $this->data['error'] ?? null;
    }

    public function getMetrics(): ?array
    {
        return $this->data['metrics'] ?? null;
    }

    public function getExecutionTime(): ?int
    {
        return $this->data['executionTime'] ?? null;
    }

    public function getDelayTime(): ?int
    {
        return $this->data['delayTime'] ?? null;
    }

    public function getWorkerId(): ?string
    {
        return $this->data['workerId'] ?? null;
    }

    public function getStream(): ?array
    {
        return $this->data['stream'] ?? null;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
