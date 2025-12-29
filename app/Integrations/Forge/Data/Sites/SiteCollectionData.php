<?php

namespace App\Integrations\Forge\Data\Sites;

use Spatie\LaravelData\Data;

class SiteCollectionData extends Data
{
    public function __construct(
        /** @var SiteData[] */
        public array $sites
    ) {
    }
}
