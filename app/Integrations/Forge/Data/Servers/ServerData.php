<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Servers;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ServerData extends Data
{
    public function __construct(
        public int $id,
        public ?int $credentialId,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $provider = null,
        public ?string $identifier = null,
        public ?string $size = null,
        public ?string $region = null,
        public ?string $ubuntuVersion = null,
        public ?string $dbStatus = null,
        public ?string $redisStatus = null,
        public ?string $phpVersion = null,
        public ?string $phpCliVersion = null,
        public ?string $opcacheStatus = null,
        public ?string $databaseType = null,
        public ?string $ipAddress = null,
        public ?int $sshPort = null,
        public ?string $privateIpAddress = null,
        public ?string $localPublicKey = null,
        public ?string $blackfireStatus = null,
        public ?string $papertrailStatus = null,
        public ?bool $revoked = null,
        public ?string $createdAt = null,
        public ?bool $isReady = null,
        public ?string $slug = null,
        public ?string $updatedAt = null,
        public ?string $connectionStatus = null,
        public ?string $timezone = null,
        /** @var array<int, string>|null */
        public ?array $tags = null,
        /** @var array<int, int>|null */
        public ?array $network = null,
        public ?string $sudoPassword = null,
        public ?string $databasePassword = null,
    ) {
    }
}
