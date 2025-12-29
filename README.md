# Laravel Forge MCP Server

[![Docker](https://img.shields.io/badge/docker-ready-blue.svg)](https://hub.docker.com/r/ddrcn/forge-mcp)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20.svg)](https://laravel.com)
[![MCP](https://img.shields.io/badge/MCP-2024--11--05-green.svg)](https://modelcontextprotocol.io)

A Model Context Protocol (MCP) server for managing Laravel Forge servers, sites, and deployments. Compatible with any MCP client.

## Features

- **179 Tools** - Complete control over the Forge API
- **9 Resources** - Integrated documentation and best practices
- **6 Prompts** - Guided workflows for common operations
- **Docker Ready** - Optimized container for easy distribution

## Quick Start

### Prerequisites

- Docker installed
- Forge API token ([get one here](https://forge.laravel.com/user-profile/api))
- Any MCP-compatible client

### Configuration

Add the server to your MCP client configuration:

```json
{
    "mcpServers": {
        "forge": {
            "command": "docker",
            "args": [
                "run",
                "--rm",
                "-i",
                "-e",
                "FORGE_API_TOKEN",
                "ddrcn/forge-mcp:latest"
            ],
            "env": {
                "FORGE_API_TOKEN": "your_token_here"
            }
        }
    }
}
```

**Replace `your_token_here` with your Forge API token.**

### Running Locally (without Docker)

```json
{
    "mcpServers": {
        "forge": {
            "command": "php",
            "args": ["/path/to/forge-mcp/artisan", "mcp:start", "forge"],
            "env": {
                "FORGE_API_TOKEN": "your_token_here"
            }
        }
    }
}
```

## Available Tools

### Servers (13 tools)

| Tool                            | Description                  |
| ------------------------------- | ---------------------------- |
| `list-servers-tool`             | List all servers             |
| `get-server-tool`               | Get server details           |
| `create-server-tool`            | Create a new server          |
| `update-server-tool`            | Update server settings       |
| `delete-server-tool`            | Delete a server              |
| `reboot-server-tool`            | Reboot a server              |
| `update-database-password-tool` | Regenerate database password |
| `revoke-server-access-tool`     | Revoke Forge SSH access      |
| `reconnect-server-tool`         | Reconnect to server          |
| `reactivate-server-tool`        | Reactivate deleted server    |
| `get-server-log-tool`           | Get server log files         |
| `list-events-tool`              | List server events           |
| `get-event-output-tool`         | Get event output             |

### Sites (18 tools)

| Tool                         | Description                   |
| ---------------------------- | ----------------------------- |
| `list-sites-tool`            | List all sites on a server    |
| `get-site-tool`              | Get site details              |
| `get-site-log-tool`          | Get site logs                 |
| `create-site-tool`           | Create a new site             |
| `update-site-tool`           | Update site settings          |
| `delete-site-tool`           | Delete a site                 |
| `change-php-version-tool`    | Change PHP version for a site |
| `clear-site-log-tool`        | Clear site log files          |
| `list-aliases-tool`          | List domain aliases           |
| `update-aliases-tool`        | Update domain aliases         |
| `get-load-balancing-tool`    | Get load balancing config     |
| `update-load-balancing-tool` | Update load balancing         |
| `install-wordpress-tool`     | Install WordPress on site     |
| `uninstall-wordpress-tool`   | Remove WordPress from site    |
| `install-phpmyadmin-tool`    | Install phpMyAdmin            |
| `uninstall-phpmyadmin-tool`  | Remove phpMyAdmin             |
| `get-packages-auth-tool`     | Get Composer auth config      |
| `update-packages-auth-tool`  | Update Composer auth config   |

### Deployments (11 tools)

| Tool                                     | Description                     |
| ---------------------------------------- | ------------------------------- |
| `deploy-site-tool`                       | Trigger a deployment            |
| `get-deployment-log-tool`                | Get deployment logs             |
| `get-deployment-script-tool`             | Get deployment script           |
| `update-deployment-script-tool`          | Update deployment script        |
| `enable-quick-deploy-tool`               | Enable auto-deploy on push      |
| `disable-quick-deploy-tool`              | Disable auto-deploy             |
| `list-deployment-history-tool`           | List deployment history         |
| `get-deployment-history-deployment-tool` | Get specific deployment         |
| `get-deployment-history-output-tool`     | Get deployment output           |
| `reset-deployment-state-tool`            | Reset stuck deployment state    |
| `set-deployment-failure-emails-tool`     | Set failure notification emails |

### SSL Certificates (7 tools)

| Tool                                   | Description                      |
| -------------------------------------- | -------------------------------- |
| `list-certificates-tool`               | List all certificates            |
| `get-certificate-tool`                 | Get certificate details          |
| `obtain-lets-encrypt-certificate-tool` | Obtain Let's Encrypt certificate |
| `install-certificate-tool`             | Install custom certificate       |
| `get-certificate-signing-request-tool` | Get CSR                          |
| `delete-certificate-tool`              | Delete a certificate             |
| `activate-certificate-tool`            | Activate SSL certificate         |

### Databases (10 tools)

| Tool                        | Description                |
| --------------------------- | -------------------------- |
| `list-databases-tool`       | List databases             |
| `get-database-tool`         | Get database details       |
| `create-database-tool`      | Create database            |
| `update-database-tool`      | Update database            |
| `delete-database-tool`      | Delete database            |
| `list-database-users-tool`  | List database users        |
| `get-database-user-tool`    | Get user details           |
| `create-database-user-tool` | Create database user       |
| `update-database-user-tool` | Update user permissions    |
| `sync-database-tool`        | Sync databases from server |

### Composite Tools (5 tools)

| Tool                         | Description                                                |
| ---------------------------- | ---------------------------------------------------------- |
| `server-health-check-tool`   | Comprehensive server health check with metrics aggregation |
| `site-status-dashboard-tool` | Complete site dashboard with SSL, deployments, workers     |
| `bulk-deploy-tool`           | Deploy multiple sites at once across servers               |
| `ssl-expiration-check-tool`  | Check SSL certificate expiration across all sites          |
| `clone-site-tool`            | Clone site configuration to new domain/server              |

### Additional Tools

| Category        | Count | Description                                                                            |
| --------------- | ----- | -------------------------------------------------------------------------------------- |
| Backups         | 7     | Backup configurations and restore                                                      |
| Commands        | 3     | Execute and monitor site commands                                                      |
| Composite       | 5     | Aggregate tools (health check, bulk deploy, clone site)                                |
| Configuration   | 4     | Nginx config and .env file management                                                  |
| Credentials     | 1     | List provider credentials                                                              |
| Daemons         | 5     | Manage long-running processes                                                          |
| Firewall        | 4     | Manage firewall rules                                                                  |
| Git             | 5     | Repository and deploy key management                                                   |
| Integrations    | 21    | Laravel integrations (Horizon, Octane, Reverb, Pulse, Inertia, Maintenance, Scheduler) |
| Jobs            | 5     | Scheduled jobs (cron)                                                                  |
| Monitors        | 4     | Server monitoring                                                                      |
| Nginx Templates | 6     | Custom Nginx configurations                                                            |
| PHP             | 5     | PHP version management and OPcache                                                     |
| Recipes         | 6     | Reusable server scripts                                                                |
| Redirect Rules  | 4     | URL redirects                                                                          |
| Regions         | 1     | List available cloud provider regions                                                  |
| Security Rules  | 4     | HTTP authentication                                                                    |
| Services        | 15    | MySQL, Nginx, Postgres, PHP-FPM, Blackfire, Papertrail, generic start/stop/restart     |
| SSH Keys        | 4     | SSH key management                                                                     |
| User            | 1     | Authenticated user information                                                         |
| Webhooks        | 4     | Deployment webhooks                                                                    |
| Workers         | 6     | Queue workers                                                                          |

**Total: 179 tools**

## Available Resources

| Resource                    | Description                            |
| --------------------------- | -------------------------------------- |
| `forge-api-docs`            | Forge API documentation reference      |
| `deployment-guidelines`     | Step-by-step deployment guidelines     |
| `deployment-best-practices` | Best practices for Laravel deployments |
| `security-best-practices`   | Server security recommendations        |
| `troubleshooting-guide`     | Common issues and solutions            |
| `php-upgrade-guide`         | PHP version upgrade procedures         |
| `queue-worker-guide`        | Queue worker configuration guide       |
| `nginx-optimization`        | Nginx performance tuning               |
| `security-hardening`        | Advanced server security hardening     |

## Available Prompts

| Prompt                    | Description                           |
| ------------------------- | ------------------------------------- |
| `deploy-laravel-app`      | Guided Laravel application deployment |
| `setup-new-server`        | Complete server provisioning workflow |
| `migrate-site`            | Site migration between servers        |
| `troubleshoot-deployment` | Deployment failure diagnosis          |
| `ssl-renewal`             | SSL certificate renewal workflow      |
| `setup-laravel-site`      | Create Laravel site from scratch      |

## Development

### Build Docker Image

```bash
docker build -t ddrcn/forge-mcp:latest .
```

### Test Locally

```bash
# Via Docker
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' | \
  docker run --rm -i -e FORGE_API_TOKEN=your_token ddrcn/forge-mcp:latest

# Via PHP
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' | \
  FORGE_API_TOKEN=your_token php artisan mcp:start forge
```

### Run Tests

```bash
php artisan test
```

### CI/CD

This project uses GitHub Actions for continuous integration and deployment:

- **Tests**: Run automatically on push and pull requests
- **Docker**: Images are built and pushed to Docker Hub on push to master

#### Required Secrets

Configure these secrets in your GitHub repository settings:

| Secret               | Description                                                                           |
| -------------------- | ------------------------------------------------------------------------------------- |
| `DOCKERHUB_USERNAME` | Your Docker Hub username                                                              |
| `DOCKERHUB_TOKEN`    | Docker Hub access token ([create one here](https://hub.docker.com/settings/security)) |

#### Automatic Releases

Releases are created automatically based on conventional commits:

| Commit Type                     | Version Bump          | Example                     |
| ------------------------------- | --------------------- | --------------------------- |
| `feat:`                         | Minor (1.0.0 → 1.1.0) | `feat(tools): add new tool` |
| `feat!:` or `BREAKING CHANGE:`  | Major (1.0.0 → 2.0.0) | `feat!: redesign API`       |
| `fix:`, `docs:`, `chore:`, etc. | Patch (1.0.0 → 1.0.1) | `fix(api): handle errors`   |

When you push to master, the workflow will:

1. Analyze commits since last tag
2. Determine version bump
3. Create GitHub release with changelog
4. Build and push Docker images

#### Manual Release

To manually trigger a release:

```bash
git tag v1.1.0
git push origin v1.1.0
```

## Troubleshooting

### Connection Issues

1. Ensure Docker is running
2. Verify `FORGE_API_TOKEN` is set correctly
3. Restart your MCP client after configuration changes

### API Errors

```bash
# Test API directly
curl -H "Authorization: Bearer $FORGE_API_TOKEN" \
     -H "Accept: application/json" \
     https://forge.laravel.com/api/v1/servers
```

## Documentation

- [Forge API Documentation](https://forge.laravel.com/api-documentation)
- [MCP Specification](https://modelcontextprotocol.io)

## License

This project is licensed under the MIT License.

## Acknowledgements

- [Laravel Forge](https://forge.laravel.com) - Server management platform
- [Model Context Protocol](https://modelcontextprotocol.io) - MCP specification
- [Laravel MCP](https://github.com/laravel/mcp) - Laravel MCP implementation
