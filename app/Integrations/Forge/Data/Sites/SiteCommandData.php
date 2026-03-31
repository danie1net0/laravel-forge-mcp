<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Sites;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SiteCommandData extends Data
{
    public function __construct(
        public int $id,
        public ?int $serverId = null,
        public ?int $siteId = null,
        public ?string $command = null,
        public ?string $status = null,
        public ?string $output = null,
        public ?string $duration = null,
        public ?int $userId = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
