<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\SSHKeys;

use Spatie\LaravelData\Data;

class SSHKeyCollectionData extends Data
{
    /**
     * @param  SSHKeyData[]  $keys
     */
    public function __construct(
        public array $keys,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $keys = array_map(
            fn (array $key): SSHKeyData => SSHKeyData::from($key),
            $data['keys'] ?? []
        );

        return new self(keys: $keys);
    }
}
