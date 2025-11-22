# Laravel Forge MCP Server

A Model Context Protocol (MCP) server for Laravel Forge, enabling AI assistants to interact with your Forge infrastructure.

## Overview

This MCP server provides a comprehensive set of tools to manage Laravel Forge servers, sites, databases, SSL certificates, and more through AI assistants like Claude.

## Features

- ✅ Server management (list, get details)
- ✅ Site management (list, get details, deploy)
- ✅ SSL certificate management (list, obtain Let's Encrypt)
- ✅ Database management (list)
- ✅ Scheduled job management (list cron jobs)
- ✅ Daemon management (list background processes)
- ✅ Deployment operations (view logs, scripts, trigger deploys)
- ✅ Firewall management (list rules)
- ✅ Server operations (reboot)

## Installation

### 1. Clone and Install

```bash
git clone <repository-url>
cd forge-mcp
composer install
npm install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Add your Forge API token to `.env`:

```env
FORGE_API_TOKEN=your_forge_api_token_here
```

Get your API token from [Forge Account Settings](https://forge.laravel.com/user-profile/api).

### 3. Setup Database

```bash
php artisan migrate
```

## MCP Server Configuration

The MCP server is configured in `.mcp.json`:

```json
{
    "mcpServers": {
        "forge": {
            "command": "php",
            "args": [
                "artisan",
                "mcp:start",
                "forge"
            ]
        }
    }
}
```

## Available Tools

### Server Tools

#### `list-servers-tool`
List all servers in your Forge account.

**Returns:**
- Server ID, name, IP address, region, PHP version, provider

**Example:**
```
Server: producao (ID: 943281)
IP: 20.197.225.63
PHP: php84
Status: ready
```

#### `get-server-tool`
Get detailed information about a specific server.

**Parameters:**
- `server_id` (required): The Forge server ID

**Returns:**
Complete server details including configuration, network settings, and installed software.

#### `reboot-server-tool`
Reboot a Forge server.

**Parameters:**
- `server_id` (required): The Forge server ID

⚠️ **Warning:** This will restart the entire server and cause downtime for all sites.

### Site Tools

#### `list-sites-tool`
List all sites on a specific server.

**Parameters:**
- `server_id` (required): The Forge server ID

**Returns:**
- Site ID, domain, directory, repository info, PHP version, SSL status

**Example:**
```
Site: sandbox.escsolutions.ai (ID: 2803086)
Repository: empresa-legal/empresa-legal (sandbox branch)
PHP: php84
SSL: Secured
Quick Deploy: Enabled
```

#### `get-site-tool`
Get detailed information about a specific site.

**Parameters:**
- `server_id` (required): The Forge server ID
- `site_id` (required): The site ID

**Returns:**
Complete site configuration including deployment settings, repository details, and status.

#### `deploy-site-tool`
Trigger a deployment for a specific site.

**Parameters:**
- `server_id` (required): The Forge server ID
- `site_id` (required): The site ID

**What it does:**
1. Pulls latest code from git repository
2. Installs dependencies (Composer, npm)
3. Runs migrations
4. Clears and rebuilds caches
5. Restarts PHP-FPM

⚠️ **Note:** Site will be briefly unavailable during deployment.

#### `get-deployment-log-tool`
Get the latest deployment log for a site.

**Parameters:**
- `server_id` (required): The Forge server ID
- `site_id` (required): The site ID

**Returns:**
Complete deployment log including git output, dependency installation, and any errors.

#### `get-deployment-script-tool`
Get the deployment script for a site.

**Parameters:**
- `server_id` (required): The Forge server ID
- `site_id` (required): The site ID

**Returns:**
The bash script that runs during deployments.

### SSL Certificate Tools

#### `list-certificates-tool`
List all SSL certificates for a site.

**Parameters:**
- `server_id` (required): The Forge server ID
- `site_id` (required): The site ID

**Returns:**
- Certificate ID, domains, type (Let's Encrypt or Custom), status, expiration

#### `obtain-lets-encrypt-certificate-tool`
Obtain and install a free Let's Encrypt SSL certificate.

**Parameters:**
- `server_id` (required): The Forge server ID
- `site_id` (required): The site ID
- `domains` (required): Array of domain names

**Requirements:**
- Domain must point to the server's IP address
- Port 80 must be accessible for verification

**Example:**
```json
{
  "server_id": 943281,
  "site_id": 2803086,
  "domains": ["sandbox.escsolutions.ai", "www.sandbox.escsolutions.ai"]
}
```

⚠️ **Rate Limits:** Let's Encrypt has rate limits (5 certificates per domain per week).

### Database Tools

#### `list-databases-tool`
List all databases on a server.

**Parameters:**
- `server_id` (required): The Forge server ID

**Returns:**
- Database ID, name, status, creation date

### Scheduled Job Tools

#### `list-scheduled-jobs-tool`
List all cron jobs on a server.

**Parameters:**
- `server_id` (required): The Forge server ID

**Returns:**
- Job ID, command, user, frequency, cron expression, status

**Example:**
```
Job: php artisan schedule:run
User: forge
Frequency: Minutely (* * * * *)
Status: installed
```

### Daemon Tools

#### `list-daemons-tool`
List all daemon processes on a server.

**Parameters:**
- `server_id` (required): The Forge server ID

**Returns:**
- Daemon ID, command, user, directory, status

**Example:**
```
Daemon: php artisan horizon
User: forge
Directory: /home/forge/system.escsolutions.ai/
Status: installed
```

### Firewall Tools

#### `list-firewall-rules-tool`
List all firewall rules on a server.

**Parameters:**
- `server_id` (required): The Forge server ID

**Returns:**
- Rule ID, name, port, IP restrictions, status

## Usage Examples

### Deploy a Site

```php
// List sites to find the site ID
$sites = forge()->listSites(943281);

// Trigger deployment
forge()->deploySite(943281, 2803086);

// Check deployment log
$log = forge()->getDeploymentLog(943281, 2803086);
```

### Manage SSL Certificates

```php
// List existing certificates
$certs = forge()->listCertificates(943281, 2803086);

// Obtain Let's Encrypt certificate
forge()->obtainLetsEncryptCertificate(943281, 2803086, [
    'domains' => ['example.com', 'www.example.com']
]);
```

### Monitor Server Resources

```php
// Get server details
$server = forge()->getServer(943281);

// List databases
$databases = forge()->listDatabases(943281);

// List scheduled jobs
$jobs = forge()->listScheduledJobs(943281);

// List daemons
$daemons = forge()->listDaemons(943281);

// List firewall rules
$rules = forge()->listFirewallRules(943281);
```

## Testing

The project includes comprehensive test coverage:

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Mcp/Tools/DeploySiteToolTest.php

# Run tests with filter
php artisan test --filter=DeploySiteToolTest
```

Current test coverage: **87 tests**

## Development

### Code Standards

The project uses Laravel Pint for code formatting:

```bash
# Format code
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

### Adding New Tools

1. Create a new tool class in `app/Mcp/Tools/`
2. Extend `BaseForgeTool`
3. Implement required methods
4. Add corresponding method to `ForgeService`
5. Write tests in `tests/Feature/Mcp/Tools/`

Example structure:

```php
<?php

namespace App\Mcp\Tools\YourCategory;

use App\Mcp\Tools\BaseForgeTool;
use Laravel\Forge\MCP\Attributes\Tool;

#[Tool(
    name: 'your-tool-name',
    description: 'Description of what your tool does'
)]
class YourToolName extends BaseForgeTool
{
    public function execute(array $arguments): array
    {
        // Your implementation
    }
}
```

## Security

### API Token

Store your Forge API token securely in `.env`. Never commit tokens to version control.

### Destructive Operations

Tools that modify infrastructure (deploy, reboot, delete) require explicit confirmation. Use with caution, especially in production environments.

### Read-Only vs. Write Operations

- **Read-Only:** Safe to use anytime (list, get)
- **Write Operations:** Modify infrastructure (deploy, reboot, create, delete)

## Troubleshooting

### "Script timed out" Error

Deployments can take several minutes. If you get a timeout error, check the deployment log to see if it completed successfully.

### SSL Certificate Failures

- Verify domain DNS points to server IP
- Ensure port 80 is open in firewall
- Check Let's Encrypt rate limits

### Connection Errors

- Verify `FORGE_API_TOKEN` is set correctly
- Check network connectivity to Forge API
- Ensure API token has necessary permissions

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Write tests for new features
4. Ensure all tests pass
5. Follow PSR-12 coding standards
6. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

- [Laravel Forge Documentation](https://forge.laravel.com/docs)
- [Forge API Documentation](https://forge.laravel.com/api-documentation)
- [Model Context Protocol Documentation](https://modelcontextprotocol.io)

## Credits

Built with:
- [Laravel Framework](https://laravel.com)
- [Laravel Forge SDK](https://github.com/laravel/forge-sdk)
- [Model Context Protocol](https://modelcontextprotocol.io)