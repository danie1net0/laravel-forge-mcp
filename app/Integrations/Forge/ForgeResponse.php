<?php

declare(strict_types=1);

namespace App\Integrations\Forge;

use Illuminate\Support\Str;
use Saloon\Helpers\ArrayHelpers;
use Saloon\Http\Response;

class ForgeResponse extends Response
{
    /** @var array<array-key, mixed>|null */
    private ?array $normalizedJson = null;

    /**
     * @param array-key|null $key
     * @return ($key is null ? array<array-key, mixed> : mixed)
     */
    public function json(string|int|null $key = null, mixed $default = null): mixed
    {
        if (! isset($this->normalizedJson)) {
            /** @var array<array-key, mixed> $raw */
            $raw = parent::json();
            $this->normalizedJson = $this->normalizeJsonApiResponse($raw);
        }

        if ($key === null) {
            return $this->normalizedJson;
        }

        return ArrayHelpers::get($this->normalizedJson, $key, $default);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function normalizeJsonApiResponse(array $data): array
    {
        if (! array_key_exists('data', $data)) {
            return $data;
        }

        $dataValue = $data['data'];

        if (! is_array($dataValue)) {
            return $data;
        }

        if ($this->isJsonApiResource($dataValue)) {
            return $this->normalizeSingleResource($dataValue);
        }

        if ($this->isJsonApiCollection($dataValue)) {
            return $this->normalizeCollection($dataValue, $data);
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $resource
     */
    private function isJsonApiResource(array $resource): bool
    {
        return array_key_exists('type', $resource) && array_key_exists('attributes', $resource);
    }

    /**
     * @param array<array-key, mixed> $collection
     */
    private function isJsonApiCollection(array $collection): bool
    {
        if ($collection === []) {
            return true;
        }

        $firstItem = reset($collection);

        return is_array($firstItem) && $this->isJsonApiResource($firstItem);
    }

    /**
     * @param array<array-key, mixed> $resource
     * @return array<string, mixed>
     */
    private function extractAttributes(array $resource): array
    {
        $attributes = $resource['attributes'] ?? [];
        $resourceId = $resource['id'] ?? null;
        $attributes['id'] = $resourceId;

        if (is_numeric($resourceId)) {
            $attributes['id'] = (int) $resourceId;
        }

        return $attributes;
    }

    /**
     * @param array<array-key, mixed> $resource
     * @return array<string, mixed>
     */
    private function normalizeSingleResource(array $resource): array
    {
        $type = $this->singularizeType((string) $resource['type']);

        return [$type => $this->extractAttributes($resource)];
    }

    /**
     * @param array<array-key, mixed> $collection
     * @param array<array-key, mixed> $fullResponse
     * @return array<string, mixed>
     */
    private function normalizeCollection(array $collection, array $fullResponse): array
    {
        if ($collection === []) {
            $type = $this->guessCollectionType($fullResponse);

            return [$type => []];
        }

        $type = (string) ($collection[0]['type'] ?? 'items');
        $items = array_map(
            fn (array $item): array => $this->extractAttributes($item),
            $collection
        );

        $result = [$type => $items];

        if (isset($fullResponse['meta'])) {
            $result['meta'] = $fullResponse['meta'];
        }

        return $result;
    }

    private function singularizeType(string $type): string
    {
        $singularMap = [
            'servers' => 'server',
            'sites' => 'site',
            'databases' => 'database',
            'database-users' => 'database_user',
            'database-schemas' => 'database',
            'certificates' => 'certificate',
            'daemons' => 'daemon',
            'background-processes' => 'daemon',
            'firewall-rules' => 'rule',
            'jobs' => 'job',
            'scheduled-jobs' => 'job',
            'monitors' => 'monitor',
            'workers' => 'worker',
            'webhooks' => 'webhook',
            'deployment-webhooks' => 'webhook',
            'ssh-keys' => 'key',
            'security-rules' => 'rule',
            'redirect-rules' => 'rule',
            'nginx-templates' => 'template',
            'backup-configurations' => 'backup_configuration',
            'backups' => 'backup',
            'credentials' => 'credential',
            'users' => 'user',
            'recipes' => 'recipe',
            'regions' => 'region',
            'deployments' => 'deployment',
            'domains' => 'domain',
            'events' => 'event',
            'commands' => 'command',
        ];

        return $singularMap[$type] ?? Str::singular($type);
    }

    /**
     * @param array<array-key, mixed> $fullResponse
     */
    private function guessCollectionType(array $fullResponse): string
    {
        $links = $fullResponse['links'] ?? [];
        $path = $links['first'] ?? '';

        if (! is_string($path) || $path === '') {
            return 'items';
        }

        $pathWithoutQuery = explode('?', $path)[0];
        $segments = explode('/', $pathWithoutQuery);

        return end($segments) ?: 'items';
    }
}
