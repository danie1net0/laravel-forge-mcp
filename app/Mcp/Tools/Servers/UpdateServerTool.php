<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Servers\UpdateServerData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update server configuration on Laravel Forge.

        Allows updating server metadata like name, size, and IP address.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        **Optional Parameters:**
        - `name`: New server name
        - `size`: New server size designation
        - `ip_address`: Updated IP address
        - `private_ip_address`: Updated private IP address
        - `max_upload_size`: Maximum upload size in MB (default: 256)
        - `network`: Network configuration

        **Warning:** This updates metadata only. To resize actual server resources,
        you must do so through your cloud provider's control panel first.

        Returns the updated server information.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'private_ip_address' => ['nullable', 'ip'],
            'max_upload_size' => ['nullable', 'integer', 'min:1'],
            'network' => ['nullable', 'array'],
        ]);

        $serverId = $request->integer('server_id');
        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->string('name');
        }

        if ($request->has('size')) {
            $data['size'] = $request->string('size');
        }

        if ($request->has('ip_address')) {
            $data['ip_address'] = $request->string('ip_address');
        }

        if ($request->has('private_ip_address')) {
            $data['private_ip_address'] = $request->string('private_ip_address');
        }

        if ($request->has('max_upload_size')) {
            $data['max_upload_size'] = $request->integer('max_upload_size');
        }

        if ($request->has('network')) {
            $data['network'] = $request->array('network');
        }

        try {
            $updateData = UpdateServerData::from($data);
            $server = $client->servers()->update($serverId, $updateData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Server updated successfully',
                'server' => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'size' => $server->size,
                    'ip_address' => $server->ipAddress,
                    'private_ip_address' => $server->privateIpAddress,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to update server.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->min(1)
                ->required(),
            'name' => $schema->string()
                ->description('New server name'),
            'size' => $schema->string()
                ->description('New server size designation'),
            'ip_address' => $schema->string()
                ->description('Updated IP address'),
            'private_ip_address' => $schema->string()
                ->description('Updated private IP address'),
            'max_upload_size' => $schema->integer()
                ->description('Maximum upload size in MB')
                ->min(1),
            'network' => $schema->array()
                ->description('Network configuration'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
