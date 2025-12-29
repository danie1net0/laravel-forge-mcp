<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class ForgeApiDocsResource extends Resource
{
    protected string $name = 'forge-api-docs';

    protected string $uri = 'forge://docs/api';

    protected string $mimeType = 'text/markdown';

    protected string $description = 'Laravel Forge API documentation and reference guide.';

    public function handle(Request $request): Response
    {
        $content = <<<'MARKDOWN'
        # Laravel Forge API Documentation

        ## Overview

        The Laravel Forge API allows you to manage servers, sites, databases, and more programmatically.

        ## Authentication

        All API requests require authentication via a bearer token:
        - Generate tokens at: https://forge.laravel.com/user-profile/api
        - Include in header: `Authorization: Bearer YOUR_TOKEN`

        ## Common Endpoints

        ### Servers
        - `GET /servers` - List all servers
        - `GET /servers/{id}` - Get server details
        - `POST /servers` - Create new server
        - `PUT /servers/{id}` - Update server
        - `DELETE /servers/{id}` - Delete server
        - `POST /servers/{id}/reboot` - Reboot server

        ### Sites
        - `GET /servers/{serverId}/sites` - List sites
        - `GET /servers/{serverId}/sites/{siteId}` - Get site details
        - `POST /servers/{serverId}/sites` - Create site
        - `PUT /servers/{serverId}/sites/{siteId}` - Update site
        - `DELETE /servers/{serverId}/sites/{siteId}` - Delete site
        - `POST /servers/{serverId}/sites/{siteId}/deployment/deploy` - Deploy site

        ### Deployments
        - `GET /servers/{serverId}/sites/{siteId}/deployment/script` - Get deployment script
        - `PUT /servers/{serverId}/sites/{siteId}/deployment/script` - Update deployment script
        - `GET /servers/{serverId}/sites/{siteId}/deployment/log` - Get deployment log

        ### SSL Certificates
        - `GET /servers/{serverId}/sites/{siteId}/certificates` - List certificates
        - `POST /servers/{serverId}/sites/{siteId}/certificates/letsencrypt` - Install Let's Encrypt

        ### Databases
        - `GET /servers/{serverId}/databases` - List databases
        - `POST /servers/{serverId}/databases` - Create database
        - `DELETE /servers/{serverId}/databases/{id}` - Delete database

        ### Scheduled Jobs
        - `GET /servers/{serverId}/jobs` - List scheduled jobs
        - `POST /servers/{serverId}/jobs` - Create job
        - `DELETE /servers/{serverId}/jobs/{id}` - Delete job

        ### Daemons
        - `GET /servers/{serverId}/daemons` - List daemons
        - `POST /servers/{serverId}/daemons` - Create daemon
        - `POST /servers/{serverId}/daemons/{id}/restart` - Restart daemon
        - `DELETE /servers/{serverId}/daemons/{id}` - Delete daemon

        ### Firewall Rules
        - `GET /servers/{serverId}/firewall-rules` - List rules
        - `POST /servers/{serverId}/firewall-rules` - Create rule
        - `DELETE /servers/{serverId}/firewall-rules/{id}` - Delete rule

        ## Rate Limiting

        - 60 requests per minute per API token
        - Rate limit headers included in responses

        ## Error Handling

        - `400` - Bad Request (validation errors)
        - `401` - Unauthorized (invalid token)
        - `403` - Forbidden (insufficient permissions)
        - `404` - Not Found
        - `422` - Unprocessable Entity
        - `429` - Too Many Requests (rate limited)
        - `500` - Server Error

        ## Resources

        - [Official API Documentation](https://forge.laravel.com/api-documentation)
        - [Laravel Forge SDK](https://github.com/laravel/forge-sdk)
        MARKDOWN;

        return Response::text($content);
    }
}
