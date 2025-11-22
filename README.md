# Laravel Forge MCP Server

A Model Context Protocol (MCP) server for Laravel Forge, enabling AI assistants to manage your Forge infrastructure.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Add your Forge API token to `.env`:

```env
FORGE_API_TOKEN=your_token_here
```

Get your API token from [Forge Account Settings](https://forge.laravel.com/user-profile/api).

## Usage

### Quick Start (Claude Desktop)

Add to `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "forge": {
      "command": "php",
      "args": [
        "/absolute/path/to/forge-mcp/artisan",
        "mcp:start",
        "forge"
      ]
    }
  }
}
```

Restart Claude Desktop and look for the 🔌 icon.

### Other Clients

See [MCP_CLIENTS.md](MCP_CLIENTS.md) for detailed setup instructions for:
- Claude Desktop (macOS, Windows, Linux)
- Cursor
- Cline (VSCode)
- Continue (VSCode)
- Zed Editor
- MCP Inspector
- Remote/Production deployments

## Available Resources

### Tools (14)

**Servers:**
- `list-servers-tool` - List all servers
- `get-server-tool` - Get server details
- `reboot-server-tool` - Reboot a server

**Sites:**
- `list-sites-tool` - List sites on a server
- `get-site-tool` - Get site details

**Deployments:**
- `deploy-site-tool` - Deploy a site
- `get-deployment-script-tool` - Get deployment script
- `get-deployment-log-tool` - Get deployment log

**SSL Certificates:**
- `list-certificates-tool` - List SSL certificates
- `obtain-lets-encrypt-certificate-tool` - Install Let's Encrypt SSL

**Infrastructure:**
- `list-databases-tool` - List databases
- `list-scheduled-jobs-tool` - List cron jobs
- `list-daemons-tool` - List background processes
- `list-firewall-rules-tool` - List firewall rules

### Resources (2)

- `forge-api-docs` - Complete Forge API documentation
- `deployment-guidelines` - Best practices for deployments

### Prompts (1)

- `deploy-application` - Interactive deployment workflow

## Testing

```bash
php artisan test
```

## Development

```bash
# Code formatting
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

## Documentation

For detailed API usage and examples, refer to the official documentation:

- [Laravel Forge Documentation](https://forge.laravel.com/docs)
- [Forge API Documentation](https://forge.laravel.com/api-documentation)
- [Laravel Forge SDK](https://github.com/laravel/forge-sdk)
- [Model Context Protocol](https://modelcontextprotocol.io)

## License

MIT
