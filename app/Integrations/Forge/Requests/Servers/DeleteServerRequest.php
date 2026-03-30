<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteServerRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly bool $preserveAtProvider = false,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}";
    }

    /** @return array<string, bool> */
    protected function defaultQuery(): array
    {
        if (! $this->preserveAtProvider) {
            return [];
        }

        return ['preserve_at_provider' => true];
    }
}
