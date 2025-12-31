<?php

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
        public string $name,
        public string $type,
        public string $provider,
        public ?string $identifier,
        public string $size,
        public string $region,
        public string $ubuntuVersion,
        public ?string $dbStatus,
        public ?string $redisStatus,
        public ?string $phpVersion,
        public ?string $phpCliVersion,
        public ?string $opcacheStatus,
        public ?string $databaseType,
        public ?string $ipAddress,
        public int $sshPort,
        public ?string $privateIpAddress,
        public ?string $localPublicKey,
        public ?string $blackfireStatus,
        public ?string $papertrailStatus,
        public bool $revoked,
        public string $createdAt,
        public bool $isReady,
        public array $tags,
        public array $network,
        public ?string $sudoPassword = null,
        public ?string $databasePassword = null,
    ) {
    }
}
