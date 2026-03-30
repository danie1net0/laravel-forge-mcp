<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeResponse;
use GuzzleHttp\Psr7\{Request as GuzzleRequest, Response as GuzzleResponse};
use Saloon\Http\PendingRequest;

function createForgeResponse(array $body, int $status = 200): ForgeResponse
{
    $psrResponse = new GuzzleResponse(
        status: $status,
        headers: ['Content-Type' => 'application/json'],
        body: json_encode($body),
    );

    $psrRequest = new GuzzleRequest('GET', 'https://forge.laravel.com/api/test');
    $pendingRequest = Mockery::mock(PendingRequest::class);

    return new ForgeResponse($psrResponse, $pendingRequest, $psrRequest);
}

describe('ForgeResponse', function (): void {
    it('passes through non-JSON:API responses unchanged', function (): void {
        $response = createForgeResponse(['name' => 'test', 'status' => 'active']);

        expect($response->json())
            ->toBe(['name' => 'test', 'status' => 'active']);
    });

    it('normalizes a single JSON:API resource', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '1',
                'type' => 'servers',
                'attributes' => ['name' => 'test-server'],
            ],
        ]);

        expect($response->json())
            ->toBe(['server' => ['name' => 'test-server', 'id' => 1]]);
    });

    it('normalizes a JSON:API collection', function (): void {
        $response = createForgeResponse([
            'data' => [
                ['id' => '1', 'type' => 'servers', 'attributes' => ['name' => 'server-1']],
                ['id' => '2', 'type' => 'servers', 'attributes' => ['name' => 'server-2']],
            ],
        ]);

        expect($response->json())
            ->toBe([
                'servers' => [
                    ['name' => 'server-1', 'id' => 1],
                    ['name' => 'server-2', 'id' => 2],
                ],
            ]);
    });

    it('normalizes an empty collection with links', function (): void {
        $response = createForgeResponse([
            'data' => [],
            'links' => ['first' => 'https://forge.laravel.com/api/orgs/acme/servers'],
        ]);

        expect($response->json())
            ->toBe(['servers' => []]);
    });

    it('normalizes an empty collection without links to items', function (): void {
        $response = createForgeResponse(['data' => []]);

        expect($response->json())
            ->toBe(['items' => []]);
    });

    it('includes pagination meta in collection result', function (): void {
        $response = createForgeResponse([
            'data' => [
                ['id' => '1', 'type' => 'servers', 'attributes' => ['name' => 'server-1']],
            ],
            'meta' => ['current_page' => 1, 'last_page' => 3],
        ]);

        expect($response->json())
            ->toBe([
                'servers' => [
                    ['name' => 'server-1', 'id' => 1],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 3],
            ]);
    });

    it('keeps string ID when non-numeric', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => 'abc-uuid',
                'type' => 'servers',
                'attributes' => ['name' => 'test'],
            ],
        ]);

        expect($response->json('server.id'))
            ->toBe('abc-uuid');
    });

    it('converts numeric string ID to integer', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '42',
                'type' => 'servers',
                'attributes' => ['name' => 'test'],
            ],
        ]);

        expect($response->json('server.id'))
            ->toBe(42);
    });

    it('returns nested data via key access', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '1',
                'type' => 'servers',
                'attributes' => ['name' => 'my-server'],
            ],
        ]);

        expect($response->json('server'))
            ->toBe(['name' => 'my-server', 'id' => 1]);
    });

    it('returns default when key not found', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '1',
                'type' => 'servers',
                'attributes' => ['name' => 'test'],
            ],
        ]);

        expect($response->json('nonexistent', 'fallback'))
            ->toBe('fallback');
    });

    it('passes through when data value is not an array', function (): void {
        $response = createForgeResponse(['data' => 'string-value']);

        expect($response->json())
            ->toBe(['data' => 'string-value']);
    });

    it('passes through data without type and attributes', function (): void {
        $response = createForgeResponse(['data' => ['foo' => 'bar']]);

        expect($response->json())
            ->toBe(['data' => ['foo' => 'bar']]);
    });

    it('singularizes known types correctly', function (string $type, string $expectedSingular): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '1',
                'type' => $type,
                'attributes' => ['name' => 'test'],
            ],
        ]);

        expect($response->json())
            ->toHaveKey($expectedSingular);
    })->with([
        'servers' => ['servers', 'server'],
        'sites' => ['sites', 'site'],
        'databases' => ['databases', 'database'],
        'database-users' => ['database-users', 'database_user'],
        'database-schemas' => ['database-schemas', 'database'],
        'certificates' => ['certificates', 'certificate'],
        'daemons' => ['daemons', 'daemon'],
        'background-processes' => ['background-processes', 'daemon'],
        'firewall-rules' => ['firewall-rules', 'rule'],
        'jobs' => ['jobs', 'job'],
        'scheduled-jobs' => ['scheduled-jobs', 'job'],
        'monitors' => ['monitors', 'monitor'],
        'workers' => ['workers', 'worker'],
        'webhooks' => ['webhooks', 'webhook'],
        'deployment-webhooks' => ['deployment-webhooks', 'webhook'],
        'ssh-keys' => ['ssh-keys', 'key'],
        'security-rules' => ['security-rules', 'rule'],
        'redirect-rules' => ['redirect-rules', 'rule'],
        'nginx-templates' => ['nginx-templates', 'template'],
        'backup-configurations' => ['backup-configurations', 'backup_configuration'],
        'backups' => ['backups', 'backup'],
        'credentials' => ['credentials', 'credential'],
        'users' => ['users', 'user'],
        'recipes' => ['recipes', 'recipe'],
        'regions' => ['regions', 'region'],
        'deployments' => ['deployments', 'deployment'],
        'domains' => ['domains', 'domain'],
        'events' => ['events', 'event'],
        'commands' => ['commands', 'command'],
    ]);

    it('uses Str::singular for unknown types', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '1',
                'type' => 'environments',
                'attributes' => ['name' => 'production'],
            ],
        ]);

        expect($response->json())
            ->toHaveKey('environment');
    });

    it('caches normalized result on subsequent calls', function (): void {
        $response = createForgeResponse([
            'data' => [
                'id' => '1',
                'type' => 'servers',
                'attributes' => ['name' => 'test'],
            ],
        ]);

        $firstCall = $response->json();
        $secondCall = $response->json();

        expect($firstCall)->toBe($secondCall);
    });

    it('guesses collection type from links with query string', function (): void {
        $response = createForgeResponse([
            'data' => [],
            'links' => ['first' => 'https://forge.laravel.com/api/orgs/acme/sites?page=1'],
        ]);

        expect($response->json())
            ->toBe(['sites' => []]);
    });

    it('returns items when links first is empty string', function (): void {
        $response = createForgeResponse([
            'data' => [],
            'links' => ['first' => ''],
        ]);

        expect($response->json())
            ->toBe(['items' => []]);
    });

    it('returns items when links first is not a string', function (): void {
        $response = createForgeResponse([
            'data' => [],
            'links' => ['first' => 123],
        ]);

        expect($response->json())
            ->toBe(['items' => []]);
    });
});
