# Laravel Forge MCP Server - Implementation Plan

## Overview

This document outlines the complete implementation plan for the Laravel Forge MCP (Model Context Protocol) server. The MCP server will expose Laravel Forge API functionality through tools, resources, and prompts that can be used by AI clients.

## Project Context

- **Framework:** Laravel 12
- **PHP Version:** 8.4
- **MCP Package:** laravel/mcp v0.3.4 (already installed)
- **Forge SDK:** laravel/forge-sdk (to be installed)
- **Additional Dependencies:** spatie/laravel-data v4.18 (available for DTOs)

## Implementation Strategy

**Approach:** Incremental development - implement and test each phase before advancing to the next.

**Coverage:** All Laravel Forge functionalities (servers, sites, deployments, SSL, databases, jobs, daemons, etc.)

**Components:** Full implementation including Tools, Resources, and Prompts

---

## Phase 1: Setup and Foundation

### 1.1 Install Laravel Forge SDK

```bash
composer require laravel/forge-sdk
```

### 1.2 Environment Configuration

Add to `.env`:
```env
FORGE_API_TOKEN=your_forge_api_token_here
```

Add to `config/services.php`:
```php
'forge' => [
    'api_token' => env('FORGE_API_TOKEN'),
],
```

### 1.3 Create ForgeService Wrapper

Create `app/Services/ForgeService.php` to wrap the Forge SDK and provide consistent error handling and response formatting.

**Methods to implement:**
- Server management (list, get, create, update, delete, reboot)
- Site management (list, get, create, update, delete)
- Deployment operations (deploy, get deployment log, update deployment script)
- SSL certificate management (list, get, install, delete)
- Database management (list, create, update, delete)
- Scheduled jobs management (list, create, delete)
- Daemon management (list, create, restart, delete)
- User management (list, create, update, delete)
- Firewall rules (list, create, delete)

### 1.4 Update ForgeServer Class

Update `app/Mcp/Servers/ForgeServer.php` with:
- Server name and version
- Comprehensive instructions in Markdown
- Tool registrations (will be added incrementally)
- Resource registrations (will be added in Phase 5)
- Prompt registrations (will be added in Phase 7)

---

## Phase 2: Read-Only Tools - Servers

### 2.1 ListServersTool

**File:** `app/Mcp/Tools/Servers/ListServersTool.php`

**Description:** Lists all servers in the Forge account

**Parameters:** None

**Response:** JSON array with server list including:
- Server ID
- Name
- IP address
- Provider
- Region
- Size
- PHP version
- Database type
- Created date

**Annotation:** `#[IsReadOnly]`

### 2.2 GetServerTool

**File:** `app/Mcp/Tools/Servers/GetServerTool.php`

**Description:** Gets detailed information about a specific server

**Parameters:**
- `server_id` (required, integer): The Forge server ID

**Response:** JSON object with complete server details

**Annotation:** `#[IsReadOnly]`, `#[IsIdempotent]`

### 2.3 Testing

Test with:
```bash
composer web-mcp
# Or
php artisan mcp:inspector mcp/forge
```

---

## Phase 3: Read-Only Tools - Sites

### 3.1 ListSitesTool

**File:** `app/Mcp/Tools/Sites/ListSitesTool.php`

**Description:** Lists all sites on a specific server

**Parameters:**
- `server_id` (required, integer): The Forge server ID

**Response:** JSON array with site list including:
- Site ID
- Name
- Directory
- Repository
- Branch
- Quick deploy status
- Created date

**Annotation:** `#[IsReadOnly]`

### 3.2 GetSiteTool

**File:** `app/Mcp/Tools/Sites/GetSiteTool.php`

**Description:** Gets detailed information about a specific site

**Parameters:**
- `server_id` (required, integer): The Forge server ID
- `site_id` (required, integer): The site ID

**Response:** JSON object with complete site details

**Annotation:** `#[IsReadOnly]`, `#[IsIdempotent]`

---

## Phase 4: Read-Only Tools - Other Entities

### 4.1 SSL Certificates

**Tools to create:**
- `ListCertificatesTool` - List all SSL certificates for a site
- `GetCertificateTool` - Get certificate details

### 4.2 Databases

**Tools to create:**
- `ListDatabasesTool` - List all databases on a server
- `GetDatabaseTool` - Get database details
- `ListDatabaseUsersTool` - List database users

### 4.3 Scheduled Jobs

**Tools to create:**
- `ListScheduledJobsTool` - List all scheduled jobs on a server
- `GetScheduledJobTool` - Get job details

### 4.4 Daemons

**Tools to create:**
- `ListDaemonsTool` - List all daemons on a server
- `GetDaemonTool` - Get daemon details

### 4.5 Deployment History

**Tools to create:**
- `ListDeploymentsTool` - List deployment history for a site
- `GetDeploymentLogTool` - Get deployment log details

### 4.6 Additional Read-Only Tools

- `GetDeploymentScriptTool` - Get the current deployment script
- `ListFirewallRulesTool` - List firewall rules on a server
- `ListServerUsersTool` - List server users (sudo/non-sudo)
- `GetServerMetricsTool` - Get server metrics (if available via API)

**All tools in this phase:** `#[IsReadOnly]`

---

## Phase 5: Resources

Resources provide context and documentation to AI clients.

### 5.1 ForgeApiDocsResource

**File:** `app/Mcp/Resources/ForgeApiDocsResource.php`

**URI:** `forge://docs/api`

**Description:** Complete Laravel Forge API documentation

**Content:**
- API endpoints overview
- Authentication information
- Common response formats
- Error codes and handling
- Rate limiting information

### 5.2 ServerTemplatesResource

**File:** `app/Mcp/Resources/ServerTemplatesResource.php`

**URI:** `forge://templates/servers`

**Description:** Common server configuration templates

**Content:**
- Recommended server sizes by use case
- Common PHP configurations
- Database setup templates
- Popular provider/region combinations

### 5.3 DeploymentGuidelinesResource

**File:** `app/Mcp/Resources/DeploymentGuidelinesResource.php`

**URI:** `forge://docs/deployment`

**Description:** Best practices for deployments

**Content:**
- Zero-downtime deployment strategies
- Environment variable management
- Database migration handling
- Common deployment script patterns
- Rollback procedures

---

## Phase 6: Action Tools (Write/Destructive)

### 6.1 Server Management

**Tools to create:**
- `CreateServerTool` - Create a new server (`#[IsDestructive]`)
- `UpdateServerTool` - Update server configuration (`#[IsDestructive]`)
- `DeleteServerTool` - Delete a server (`#[IsDestructive]`)
- `RebootServerTool` - Reboot a server (`#[IsDestructive]`)

### 6.2 Site Management

**Tools to create:**
- `CreateSiteTool` - Create a new site (`#[IsDestructive]`)
- `UpdateSiteTool` - Update site configuration (`#[IsDestructive]`)
- `DeleteSiteTool` - Delete a site (`#[IsDestructive]`)
- `InstallGitRepositoryTool` - Install a Git repository (`#[IsDestructive]`)
- `UpdateEnvironmentFileTool` - Update .env file (`#[IsDestructive]`)

### 6.3 Deployment

**Tools to create:**
- `DeploySiteTool` - Trigger a site deployment (`#[IsDestructive]`)
- `UpdateDeploymentScriptTool` - Update deployment script (`#[IsDestructive]`)
- `ToggleQuickDeployTool` - Enable/disable quick deploy (`#[IsIdempotent]`)

### 6.4 SSL Certificates

**Tools to create:**
- `InstallLetsEncryptCertificateTool` - Install Let's Encrypt certificate (`#[IsDestructive]`)
- `InstallCustomCertificateTool` - Install custom certificate (`#[IsDestructive]`)
- `DeleteCertificateTool` - Delete a certificate (`#[IsDestructive]`)

### 6.5 Database Management

**Tools to create:**
- `CreateDatabaseTool` - Create a new database (`#[IsDestructive]`)
- `UpdateDatabaseTool` - Update database configuration (`#[IsDestructive]`)
- `DeleteDatabaseTool` - Delete a database (`#[IsDestructive]`)
- `CreateDatabaseUserTool` - Create database user (`#[IsDestructive]`)
- `UpdateDatabaseUserTool` - Update database user (`#[IsDestructive]`)
- `DeleteDatabaseUserTool` - Delete database user (`#[IsDestructive]`)

### 6.6 Scheduled Jobs

**Tools to create:**
- `CreateScheduledJobTool` - Create a new scheduled job (`#[IsDestructive]`)
- `DeleteScheduledJobTool` - Delete a scheduled job (`#[IsDestructive]`)

### 6.7 Daemons

**Tools to create:**
- `CreateDaemonTool` - Create a new daemon (`#[IsDestructive]`)
- `RestartDaemonTool` - Restart a daemon (`#[IsDestructive]`)
- `DeleteDaemonTool` - Delete a daemon (`#[IsDestructive]`)

### 6.8 Firewall Rules

**Tools to create:**
- `CreateFirewallRuleTool` - Create a firewall rule (`#[IsDestructive]`)
- `DeleteFirewallRuleTool` - Delete a firewall rule (`#[IsDestructive]`)

### 6.9 Server Users

**Tools to create:**
- `CreateServerUserTool` - Create a server user (`#[IsDestructive]`)
- `UpdateServerUserTool` - Update server user (`#[IsDestructive]`)
- `DeleteServerUserTool` - Delete server user (`#[IsDestructive]`)

---

## Phase 7: Prompts

Prompts are reusable templates for common AI interactions.

### 7.1 DeployApplicationPrompt

**File:** `app/Mcp/Prompts/DeployApplicationPrompt.php`

**Name:** `deploy-application`

**Description:** Template for deploying a Laravel application to Forge

**Arguments:**
- `server_id` (required): Target server ID
- `site_id` (required): Target site ID
- `run_migrations` (optional): Whether to run migrations

**Messages:**
1. System message explaining deployment process
2. User message with deployment instructions

### 7.2 SetupNewSitePrompt

**File:** `app/Mcp/Prompts/SetupNewSitePrompt.php`

**Name:** `setup-new-site`

**Description:** Template for setting up a new site on Forge

**Arguments:**
- `server_id` (required): Target server ID
- `domain` (required): Site domain
- `repository` (optional): Git repository URL
- `branch` (optional): Git branch

**Messages:**
1. System message with site setup checklist
2. User message with configuration steps

### 7.3 InstallSSLPrompt

**File:** `app/Mcp/Prompts/InstallSSLPrompt.php`

**Name:** `install-ssl`

**Description:** Template for installing SSL certificate

**Arguments:**
- `server_id` (required): Target server ID
- `site_id` (required): Target site ID
- `certificate_type` (required): letsencrypt or custom

**Messages:**
1. System message explaining SSL installation
2. User message with SSL setup instructions

---

## Phase 8: Testing and Finalization

### 8.1 Create Pest Tests

**Test structure:**
```
tests/Feature/Mcp/
├── Tools/
│   ├── Servers/
│   │   ├── ListServersToolTest.php
│   │   ├── GetServerToolTest.php
│   │   └── ...
│   ├── Sites/
│   │   ├── ListSitesToolTest.php
│   │   ├── GetSiteToolTest.php
│   │   └── ...
│   ├── Deployments/
│   ├── Certificates/
│   ├── Databases/
│   └── Jobs/
├── Resources/
│   ├── ForgeApiDocsResourceTest.php
│   └── ...
└── Prompts/
    ├── DeployApplicationPromptTest.php
    └── ...
```

**Test coverage:**
- Tool parameter validation
- Successful responses
- Error handling
- Permission checks
- API error handling

### 8.2 Update Server Instructions

Update `ForgeServer.php` with comprehensive instructions including:
- Overview of available tools
- Authentication setup
- Usage examples
- Common workflows
- Error handling

### 8.3 Code Quality

Run Laravel Pint:
```bash
./vendor/bin/pint
```

Run tests:
```bash
php artisan test
```

Filter specific tests:
```bash
php artisan test --filter=Forge
```

---

## Directory Structure

Final structure will be:

```
app/
├── Mcp/
│   ├── Servers/
│   │   └── ForgeServer.php
│   ├── Tools/
│   │   ├── Servers/
│   │   │   ├── ListServersTool.php
│   │   │   ├── GetServerTool.php
│   │   │   ├── CreateServerTool.php
│   │   │   └── ...
│   │   ├── Sites/
│   │   │   ├── ListSitesTool.php
│   │   │   ├── GetSiteTool.php
│   │   │   ├── CreateSiteTool.php
│   │   │   └── ...
│   │   ├── Deployments/
│   │   ├── Certificates/
│   │   ├── Databases/
│   │   ├── Jobs/
│   │   ├── Daemons/
│   │   ├── Firewall/
│   │   └── Users/
│   ├── Resources/
│   │   ├── ForgeApiDocsResource.php
│   │   ├── ServerTemplatesResource.php
│   │   └── DeploymentGuidelinesResource.php
│   └── Prompts/
│       ├── DeployApplicationPrompt.php
│       ├── SetupNewSitePrompt.php
│       └── InstallSSLPrompt.php
├── Services/
│   └── ForgeService.php
└── DTOs/
    └── Forge/
        ├── ServerData.php
        ├── SiteData.php
        └── ...

tests/Feature/Mcp/
├── Tools/
├── Resources/
└── Prompts/
```

---

## Best Practices

### Tool Development

1. **Single Responsibility:** Each tool does one thing well
2. **Comprehensive Validation:** Use JSON Schema for parameter validation
3. **Error Handling:** Gracefully handle Forge API errors
4. **Documentation:** Clear descriptions for each tool
5. **Type Safety:** Use strict types and type hints
6. **Annotations:** Mark tools appropriately (IsReadOnly, IsDestructive, IsIdempotent)

### Response Format

Prefer structured JSON for complex data:
```php
return Response::text(json_encode([
    'success' => true,
    'data' => $result,
], JSON_PRETTY_PRINT));
```

### Security

1. **Token Validation:** Check for API token presence
2. **Input Validation:** Validate all user inputs
3. **Permission Checks:** Verify user has necessary permissions
4. **Sensitive Data:** Don't expose API tokens in responses

### Testing

1. **Mock Forge API:** Use mocks to avoid real API calls in tests
2. **Test All Paths:** Happy path, error cases, validation failures
3. **Test Isolation:** Each test should be independent
4. **Clear Assertions:** Use descriptive expectations

---

## Validation Checklist

Before marking each phase complete:

- [ ] All tools created and registered
- [ ] Input validation implemented
- [ ] Error handling tested
- [ ] Annotations added
- [ ] Tests written and passing
- [ ] Code formatted with Pint
- [ ] Documentation updated

---

## Success Criteria

The Laravel Forge MCP Server will be considered complete when:

1. ✅ All tools are implemented and tested
2. ✅ All resources provide useful context
3. ✅ Prompts cover common workflows
4. ✅ Tests have >80% coverage
5. ✅ Code passes Pint formatting
6. ✅ All tests pass
7. ✅ Server instructions are comprehensive
8. ✅ API token validation works correctly
9. ✅ Error handling is graceful and informative
10. ✅ Can successfully interact with Forge API for all operations

---

## Future Enhancements

Potential future additions:

- Monitoring and alerting tools
- Backup management tools
- Log viewing tools
- Recipe management (deployment recipes)
- Multi-server orchestration prompts
- Webhook integration tools
- Load balancer management
- Redis management
- Worker queue management
- Notification preferences management

---

## References

- [Laravel MCP Documentation](https://laravel.com/docs/12.x/mcp)
- [Laravel Forge API Documentation](https://forge.laravel.com/api-documentation)
- [Laravel Forge SDK Repository](https://github.com/laravel/forge-sdk)
- [Model Context Protocol Specification](https://spec.modelcontextprotocol.io/)

---

**Last Updated:** 2025-11-20
**Status:** Planning Complete - Ready for Implementation
