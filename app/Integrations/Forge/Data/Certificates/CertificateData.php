<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Certificates;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CertificateData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public string $domain,
        public ?string $requestStatus,
        public string $status,
        public string $type,
        public bool $active,
        public ?string $expiresAt,
        public string $createdAt,
        public ?string $activationError,
    ) {
    }
}
