<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Certificates;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class CertificateCollectionData extends Data
{
    /**
     * @param  array<CertificateData>  $certificates
     */
    public function __construct(
        #[DataCollectionOf(CertificateData::class)]
        public array $certificates,
    ) {
    }
}
