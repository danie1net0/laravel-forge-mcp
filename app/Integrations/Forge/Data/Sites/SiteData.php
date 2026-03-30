<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Sites;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SiteData extends Data
{
    /**
     * @param array<string, mixed>|null $repository
     * @param array<int, string>|null $aliases
     * @param array<string, mixed>|null $maintenanceMode
     * @param array<int, string>|null $sharedPaths
     * @param array<int, string>|null $tags
     */
    public function __construct(
        public int $id,
        public int $serverId,
        public string $name,
        public string $status,
        public string $createdAt,
        public ?string $url = null,
        public ?string $user = null,
        public ?bool $https = null,
        public ?string $webDirectory = null,
        public ?string $rootDirectory = null,
        public ?array $aliases = null,
        public ?string $phpVersion = null,
        public ?string $deploymentStatus = null,
        public ?bool $quickDeploy = null,
        public ?bool $isolated = null,
        public ?array $sharedPaths = null,
        public ?array $repository = null,
        public ?string $database = null,
        public ?array $maintenanceMode = null,
        public ?bool $zeroDowntimeDeployments = null,
        public ?string $deploymentScript = null,
        public ?bool $wildcards = null,
        public ?string $appType = null,
        public ?bool $usesEnvoyer = null,
        public ?string $deploymentUrl = null,
        public ?string $healthcheckUrl = null,
        public ?string $updatedAt = null,
        public ?array $tags = null,
    ) {
    }
}
