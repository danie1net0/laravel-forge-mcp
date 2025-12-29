<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Deployments;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DeploymentData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public int $type,
        public ?string $commitHash,
        public ?string $commitAuthor,
        public ?string $commitMessage,
        public string $status,
        public ?string $startedAt,
        public ?string $endedAt,
        public string $createdAt,
    ) {
    }
}
