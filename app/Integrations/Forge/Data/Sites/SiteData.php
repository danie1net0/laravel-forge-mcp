<?php

namespace App\Integrations\Forge\Data\Sites;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SiteData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $name,
        public ?array $aliases,
        public string $directory,
        public bool $wildcards,
        public string $status,
        public ?string $repository,
        public ?string $repositoryProvider,
        public ?string $repositoryBranch,
        public ?string $repositoryStatus,
        public bool $quickDeploy,
        public ?string $deploymentStatus,
        public string $projectType,
        public ?string $app,
        public ?string $appStatus,
        public ?string $hipchatRoom,
        public ?string $slackChannel,
        public ?int $telegramChatId,
        public ?string $telegramChatTitle,
        public ?string $teamsWebhookUrl,
        public ?string $discordWebhookUrl,
        public string $username,
        public ?string $balancingStatus,
        public string $createdAt,
        public ?string $deploymentUrl,
        public bool $isSecured,
        public ?string $phpVersion,
        public ?array $tags,
        public ?array $failureDeploymentEmails,
        public ?string $telegramSecret,
        public ?string $webDirectory,
    ) {
    }
}
