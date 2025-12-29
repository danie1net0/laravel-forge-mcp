<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetServerLogRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected string $file = 'auth'
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/logs";
    }

    protected function defaultQuery(): array
    {
        return [
            'file' => $this->file,
        ];
    }
}
