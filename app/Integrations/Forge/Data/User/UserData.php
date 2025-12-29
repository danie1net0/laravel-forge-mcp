<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\User;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $cardLastFour = null,
        public ?string $connectedToGithub = null,
        public ?string $connectedToGitlab = null,
        public ?string $connectedToBitbucket = null,
        public ?string $connectedToBitbucketTwo = null,
        public ?string $connectedToDigitalocean = null,
        public ?string $connectedToLinode = null,
        public ?string $connectedToVultr = null,
        public ?string $connectedToAws = null,
        public ?string $connectedToHetzner = null,
        public ?string $readyForBilling = null,
        public ?string $stripeIsActive = null,
        public ?bool $canCreateServers = null,
    ) {
    }
}
