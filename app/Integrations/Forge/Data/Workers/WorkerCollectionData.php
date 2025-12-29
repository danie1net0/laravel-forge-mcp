<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Workers;

use Spatie\LaravelData\Data;

class WorkerCollectionData extends Data
{
    /**
     * @param  WorkerData[]  $workers
     */
    public function __construct(
        public array $workers,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $workers = array_map(
            fn (array $worker): WorkerData => WorkerData::from($worker),
            $data['workers'] ?? []
        );

        return new self(workers: $workers);
    }
}
