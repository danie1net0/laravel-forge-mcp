<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Backups;

use Spatie\LaravelData\Data;

class BackupConfigurationCollectionData extends Data
{
    /**
     * @param  BackupConfigurationData[]  $backups
     */
    public function __construct(
        public array $backups,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $backups = array_map(
            fn (array $backup): BackupConfigurationData => BackupConfigurationData::from($backup),
            $data['backups'] ?? []
        );

        return new self(backups: $backups);
    }
}
