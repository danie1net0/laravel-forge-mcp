<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Jobs;

use App\Integrations\Forge\Data\Jobs\JobData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetJobRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $jobId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/jobs/{$this->jobId}";
    }

    public function createDtoFromResponse(Response $response): JobData
    {
        return JobData::from(array_merge($response->json('job'), ['server_id' => $this->serverId]));
    }
}
