<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Databases;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class DatabaseUserCollectionData extends Data
{
    /**
     * @param  array<DatabaseUserData>  $users
     */
    public function __construct(
        #[DataCollectionOf(DatabaseUserData::class)]
        public array $users,
    ) {
    }
}
