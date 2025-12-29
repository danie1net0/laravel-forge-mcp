<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Workers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RestartWorkerRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly int $workerId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/workers/{$this->workerId}/restart";
    }
}
