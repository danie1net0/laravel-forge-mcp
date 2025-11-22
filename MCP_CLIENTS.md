# MCP Client Installation Guide

This guide shows how to connect the Laravel Forge MCP server to different AI clients.

## Prerequisites

1. Install the Forge MCP server:
```bash
git clone <repository-url>
cd forge-mcp
composer install
cp .env.example .env
php artisan key:generate
```

2. Add your Forge API token to `.env`:
```env
FORGE_API_TOKEN=your_token_here
```

## Claude Desktop

### macOS

1. Open Claude Desktop config file:
```bash
code ~/Library/Application\ Support/Claude/claude_desktop_config.json
```

2. Add the Forge MCP server:
```json
{
  "mcpServers": {
    "forge": {
      "command": "php",
      "args": [
        "/Users/YOUR_USERNAME/path/to/forge-mcp/artisan",
        "mcp:start",
        "forge"
      ]
    }
  }
}
```

3. Replace `/Users/YOUR_USERNAME/path/to/forge-mcp/` with the absolute path to your project

4. Restart Claude Desktop

5. Verify connection: Look for the 🔌 icon in Claude Desktop

### Windows

1. Open Claude Desktop config file:
```powershell
notepad %APPDATA%\Claude\claude_desktop_config.json
```

2. Add the Forge MCP server:
```json
{
  "mcpServers": {
    "forge": {
      "command": "php",
      "args": [
        "C:\\path\\to\\forge-mcp\\artisan",
        "mcp:start",
        "forge"
      ]
    }
  }
}
```

3. Replace `C:\\path\\to\\forge-mcp\\` with the absolute path to your project

4. Restart Claude Desktop

### Linux

1. Open Claude Desktop config file:
```bash
nano ~/.config/Claude/claude_desktop_config.json
```

2. Add the Forge MCP server:
```json
{
  "mcpServers": {
    "forge": {
      "command": "php",
      "args": [
        "/home/YOUR_USERNAME/path/to/forge-mcp/artisan",
        "mcp:start",
        "forge"
      ]
    }
  }
}
```

3. Replace `/home/YOUR_USERNAME/path/to/forge-mcp/` with the absolute path to your project

4. Restart Claude Desktop

## Cursor

Cursor supports MCP through its `.cursorrules` configuration.

1. Create or edit `.cursorrules` in your project root:
```bash
code .cursorrules
```

2. Add the MCP configuration:
```json
{
  "mcp": {
    "servers": {
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
}
```

3. Restart Cursor

## Cline (VSCode Extension)

1. Install the Cline extension from VSCode marketplace

2. Open VSCode settings (Cmd/Ctrl + ,)

3. Search for "Cline MCP"

4. Add the MCP server configuration:
```json
{
  "cline.mcpServers": {
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

5. Reload VSCode

## Continue (VSCode Extension)

1. Install the Continue extension from VSCode marketplace

2. Open Continue config:
   - Press `Cmd/Ctrl + Shift + P`
   - Type "Continue: Open config.json"

3. Add the MCP server under `experimental.modelContextProtocolServers`:
```json
{
  "experimental": {
    "modelContextProtocolServers": [
      {
        "transport": {
          "type": "stdio",
          "command": "php",
          "args": [
            "/absolute/path/to/forge-mcp/artisan",
            "mcp:start",
            "forge"
          ]
        }
      }
    ]
  }
}
```

4. Reload VSCode

## Zed Editor

1. Open Zed settings: `Zed > Settings` (or `Cmd/Ctrl + ,`)

2. Click on "Open ~/.config/zed/settings.json"

3. Add the MCP configuration:
```json
{
  "context_servers": {
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

4. Restart Zed

## MCP Inspector (Testing/Debugging)

MCP Inspector is a tool for testing and debugging MCP servers.

```bash
npx @modelcontextprotocol/inspector php /absolute/path/to/forge-mcp/artisan mcp:start forge
```

This opens a web interface at http://localhost:5173 where you can:
- Test all available tools
- View requests/responses in real-time
- Debug MCP server issues

## Remote MCP Server (Production)

For production deployments, use the HTTP transport instead of stdio.

### 1. Deploy your MCP server

```bash
# Start the Laravel server
php artisan serve --host=0.0.0.0 --port=8000

# Or use a production web server (nginx, apache)
```

### 2. Configure client with mcp-remote

For Claude Desktop:

```json
{
  "mcpServers": {
    "forge": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/mcp-remote",
        "https://your-domain.com/mcp/forge"
      ]
    }
  }
}
```

### 3. Secure your endpoint

Add authentication to `routes/ai.php`:

```php
Mcp::web('forge')
    ->middleware(['auth:sanctum']) // Add authentication
    ->using(ForgeServer::class);
```

## Troubleshooting

### Client can't connect

1. **Check absolute paths**: Make sure you're using absolute paths, not relative
2. **Verify PHP**: Ensure `php` is in your PATH: `which php`
3. **Check .env**: Verify `FORGE_API_TOKEN` is set
4. **Test manually**: Run the command directly:
   ```bash
   php /absolute/path/to/artisan mcp:start forge
   ```

### No tools showing

1. Check that `FORGE_API_TOKEN` is set in `.env`
2. Verify the token is valid on [Forge](https://forge.laravel.com/user-profile/api)
3. Check server logs for errors

### Connection refused (remote)

1. Ensure the server is running: `php artisan serve`
2. Check firewall rules
3. Verify the URL is accessible: `curl https://your-domain.com/mcp/forge`

### SSL certificate errors (remote)

When using `mcp-remote` with local HTTPS:
- Node.js has its own certificate store
- Use HTTP during local development
- Use valid SSL certificates in production

## Testing Your Setup

After configuration, test the connection:

### In Claude Desktop
Ask: "List my Forge servers"

### In Cursor
Ask: "Show me all my Forge sites"

### In MCP Inspector
1. Open http://localhost:5173
2. Click on "list-servers-tool"
3. Click "Execute"
4. View the results

## Example Usage

Once connected, you can interact naturally with your Forge infrastructure:

- "List all my servers"
- "Deploy staging.example.com"
- "Show me the deployment log for my production site"
- "Get an SSL certificate for example.com"
- "List all databases on server 12345"
- "What cron jobs are running on my server?"

The AI assistant will use the appropriate MCP tools to complete these tasks.
