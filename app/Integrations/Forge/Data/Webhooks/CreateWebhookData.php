<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Webhooks;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateWebhookData extends Data
{
    public function __construct(
        public string $url,
    ) {
    }
}
