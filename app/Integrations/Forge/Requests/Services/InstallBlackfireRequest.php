<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Services;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class InstallBlackfireRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected string $serverIdToken,
        protected string $serverToken
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/blackfire/install";
    }

    protected function defaultBody(): array
    {
        return [
            'server_id' => $this->serverIdToken,
            'server_token' => $this->serverToken,
        ];
    }
}
