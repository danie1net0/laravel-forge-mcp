<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Jobs;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteJobRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $jobId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/jobs/{$this->jobId}";
    }
}
