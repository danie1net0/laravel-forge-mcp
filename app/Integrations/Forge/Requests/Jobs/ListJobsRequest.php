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
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/scheduled-jobs";
    }

    public function createDtoFromResponse(Response $response): JobCollectionData
    {
        $jobs = array_map(
            fn (array $job): array => array_merge($job, ['server_id' => $this->serverId]),
            $response->json('jobs')
        );

        return JobCollectionData::from(['jobs' => $jobs]);
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['page[size]' => $this->pageSize];

        if ($this->cursor !== null) {
            $query['page[cursor]'] = $this->cursor;
        }

        return $query;
    }
}
