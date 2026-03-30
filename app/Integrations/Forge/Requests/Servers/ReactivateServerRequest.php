<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ReactivateServerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/actions";
    }

    /**
     * @return array{action: string}
     */
    protected function defaultBody(): array
    {
        return [
            'action' => 'reactivate',
        ];
    }
}
