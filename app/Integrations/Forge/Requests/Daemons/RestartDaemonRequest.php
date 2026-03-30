<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Daemons;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class RestartDaemonRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $daemonId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/background-processes/{$this->daemonId}/actions";
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'action' => 'restart',
        ];
    }
}
