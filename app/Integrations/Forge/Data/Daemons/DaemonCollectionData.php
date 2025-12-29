<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Daemons;

use Spatie\LaravelData\Data;

class DaemonCollectionData extends Data
{
    /**
     * @param  DaemonData[]  $daemons
     */
    public function __construct(
        public array $daemons,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $daemons = array_map(
            fn (array $daemon): DaemonData => DaemonData::from($daemon),
            $data['daemons'] ?? []
        );

        return new self(daemons: $daemons);
    }
}
