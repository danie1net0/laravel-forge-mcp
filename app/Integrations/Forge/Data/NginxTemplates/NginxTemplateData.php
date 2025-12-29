<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\NginxTemplates;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class NginxTemplateData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $name,
        public string $content,
    ) {
    }
}
