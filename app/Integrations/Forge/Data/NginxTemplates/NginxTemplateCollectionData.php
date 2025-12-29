<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\NginxTemplates;

use Spatie\LaravelData\Data;

class NginxTemplateCollectionData extends Data
{
    /**
     * @param  NginxTemplateData[]  $templates
     */
    public function __construct(
        public array $templates,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $templates = array_map(
            fn (array $template): NginxTemplateData => NginxTemplateData::from($template),
            $data['templates'] ?? []
        );

        return new self(templates: $templates);
    }
}
