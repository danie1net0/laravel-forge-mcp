<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Workers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetWorkerOutputRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $workerId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/workers/{$this->workerId}/output";
    }
}
