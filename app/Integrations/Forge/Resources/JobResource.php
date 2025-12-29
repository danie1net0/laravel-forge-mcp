<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobCollectionData, JobData};
use App\Integrations\Forge\Requests\Jobs\{CreateJobRequest, DeleteJobRequest, GetJobOutputRequest, GetJobRequest, ListJobsRequest};

class JobResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): JobCollectionData
    {
        $request = new ListJobsRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $jobId): JobData
    {
        $request = new GetJobRequest($serverId, $jobId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateJobData $data): JobData
    {
        $request = new CreateJobRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $jobId): void
    {
        $this->connector->send(new DeleteJobRequest($serverId, $jobId));
    }

    public function getOutput(int $serverId, int $jobId): string
    {
        $response = $this->connector->send(new GetJobOutputRequest($serverId, $jobId));

        return $response->json('output') ?? '';
    }
}
