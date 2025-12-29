<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Jobs;

use Spatie\LaravelData\Data;

class JobCollectionData extends Data
{
    /**
     * @param  JobData[]  $jobs
     */
    public function __construct(
        public array $jobs,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $jobs = array_map(
            fn (array $job): JobData => JobData::from($job),
            $data['jobs'] ?? []
        );

        return new self(jobs: $jobs);
    }
}
