<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Credentials;

use Spatie\LaravelData\Data;

class CredentialCollectionData extends Data
{
    /**
     * @param  CredentialData[]  $credentials
     */
    public function __construct(
        public array $credentials,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $credentials = array_map(
            fn (array $credential): CredentialData => CredentialData::from($credential),
            $data['credentials'] ?? []
        );

        return new self(credentials: $credentials);
    }
}
