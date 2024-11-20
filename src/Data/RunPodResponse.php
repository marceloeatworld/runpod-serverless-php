<?php

namespace MarceloEatWorld\RunPod\Data;

use Saloon\Http\Response;

class RunPodResponse implements \JsonSerializable
{
    public function __construct(
        public ?string $id,
        public ?string $status,
        public array $data
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();
        
        return new self(
            id: $data['id'] ?? null,
            status: $data['status'] ?? null,
            data: $data // Garde toute la réponse brute
        );
    }

    /**
     * Statuts possibles selon la documentation RunPod
     */
    public function isCompleted(): bool
    {
        return strtoupper($this->status) === 'COMPLETED';
    }

    public function isInQueue(): bool
    {
        return strtoupper($this->status) === 'IN_QUEUE';
    }

    public function isInProgress(): bool
    {
        return strtoupper($this->status) === 'IN_PROGRESS';
    }

    public function isFailed(): bool
    {
        return strtoupper($this->status) === 'FAILED';
    }

    public function isCancelled(): bool
    {
        return strtoupper($this->status) === 'CANCELLED';
    }

    /**
     * Obtient la sortie si disponible
     */
    public function getOutput(): mixed
    {
        return $this->data['output'] ?? null;
    }

    /**
     * Obtient les métriques d'exécution
     */
    public function getMetrics(): ?array
    {
        return $this->data['metrics'] ?? null;
    }

    /**
     * Obtient le temps d'exécution
     */
    public function getExecutionTime(): ?int
    {
        return $this->data['executionTime'] ?? null;
    }

    /**
     * Obtient le temps de délai
     */
    public function getDelayTime(): ?int
    {
        return $this->data['delayTime'] ?? null;
    }

    /**
     * Obtient l'erreur si présente
     */
    public function getError()
    {
        return $this->data['error'] ?? null;
    }

    /**
     * Pour la sérialisation JSON
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}