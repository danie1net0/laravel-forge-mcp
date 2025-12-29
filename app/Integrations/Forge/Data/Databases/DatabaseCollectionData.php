<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Databases;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class DatabaseCollectionData extends Data
{
    /**
     * @param  array<DatabaseData>  $databases
     */
    public function __construct(
        #[DataCollectionOf(DatabaseData::class)]
        public array $databases,
    ) {
    }
}
