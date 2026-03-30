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
        public ?string $domain = null,
        public ?string $requestStatus = null,
        public ?string $status = null,
        public ?string $type = null,
        public ?bool $active = null,
        public ?string $expiresAt = null,
        public ?string $createdAt = null,
        public ?string $activationError = null,
    ) {
    }
}
