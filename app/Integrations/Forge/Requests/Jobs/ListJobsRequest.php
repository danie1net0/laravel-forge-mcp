<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Jobs;

use App\Integrations\Forge\Data\Jobs\JobCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListJobsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/jobs";
    }

    public function createDtoFromResponse(Response $response): JobCollectionData
    {
        $jobs = array_map(
            fn (array $job): array => array_merge($job, ['server_id' => $this->serverId]),
            $response->json('jobs')
        );

        return JobCollectionData::from(['jobs' => $jobs]);
    }
}
