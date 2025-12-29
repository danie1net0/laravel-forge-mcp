<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Monitors;

use Spatie\LaravelData\Data;

class MonitorCollectionData extends Data
{
    /**
     * @param  MonitorData[]  $monitors
     */
    public function __construct(
        public array $monitors,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $monitors = array_map(
            fn (array $monitor): MonitorData => MonitorData::from($monitor),
            $data['monitors'] ?? []
        );

        return new self(monitors: $monitors);
    }
}
