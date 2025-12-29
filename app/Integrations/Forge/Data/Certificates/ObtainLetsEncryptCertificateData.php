<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Certificates;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class ObtainLetsEncryptCertificateData extends Data
{
    /**
     * @param  array<string>  $domains
     */
    public function __construct(
        public array $domains,
    ) {
    }
}
