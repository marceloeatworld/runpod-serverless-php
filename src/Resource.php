<?php
namespace MarceloEatWorld\RunPod;

use Saloon\Http\Connector;

class Resource
{
    public function __construct(
        protected Connector $connector,
        protected string $endpointId
    ) {}
}