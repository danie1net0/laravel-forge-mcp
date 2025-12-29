<?php

namespace App\Integrations\Forge\Data\Servers;

use Spatie\LaravelData\Data;

class ServerCollectionData extends Data
{
    public function __construct(
        /** @var array<ServerData> */
        public array $servers,
    ) {
    }
}
