<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Jobs;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobData};
use Saloon\Traits\Body\HasJsonBody;

class CreateJobRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateJobData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/jobs";
    }

    public function createDtoFromResponse(Response $response): JobData
    {
        return JobData::from(array_merge($response->json('job'), ['server_id' => $this->serverId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            $this->data->toArray(),
            fn (mixed $value): bool => $value !== null
        );
    }
}
