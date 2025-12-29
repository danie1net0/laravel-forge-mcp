<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Jobs;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetJobOutputRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $jobId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/jobs/{$this->jobId}/output";
    }
}
