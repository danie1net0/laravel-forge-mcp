# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2024-12-29

### Added

- **5 New Prompts** - Guided workflows for common operations:
    - `setup-new-server` - Complete server provisioning workflow
    - `migrate-site` - Site migration between servers
    - `troubleshoot-deployment` - Deployment failure diagnosis
    - `ssl-renewal` - SSL certificate renewal workflow
    - `setup-laravel-site` - Create Laravel site from scratch

- **5 Composite Tools** - Aggregate multiple API calls:
    - `server-health-check-tool` - Comprehensive server health check
    - `site-status-dashboard-tool` - Complete site dashboard
    - `bulk-deploy-tool` - Deploy multiple sites at once
    - `ssl-expiration-check-tool` - Check SSL expiration across sites
    - `clone-site-tool` - Clone site configuration

- **4 New Resources** - Documentation guides:
    - `php-upgrade-guide` - PHP version upgrade procedures
    - `queue-worker-guide` - Queue worker configuration
    - `nginx-optimization` - Nginx performance tuning
    - `security-hardening` - Advanced server security

- **22 New API Tools**:
    - Server: `update-database-password`, `revoke-server-access`, `reconnect-server`, `reactivate-server`, `get-server-log`, `list-events`, `get-event-output`
    - Sites: `install-wordpress`, `uninstall-wordpress`, `install-phpmyadmin`, `uninstall-phpmyadmin`, `get-packages-auth`, `update-packages-auth`
    - Deployments: `reset-deployment-state`, `set-deployment-failure-emails`
    - Certificates: `activate-certificate`
    - Databases: `sync-database`
    - Jobs: `get-job-output`
    - Workers: `get-worker-output`
    - Services: `start-service`, `stop-service`, `restart-service`

- GitHub Actions CI/CD:
    - Automated tests on push/PR
    - Automatic Docker image build and push to Docker Hub
    - Multi-architecture support (amd64, arm64)

### Changed

- Total tools increased from 102 to 179
- Total resources increased from 5 to 9
- Total prompts increased from 1 to 6

## [1.0.0] - 2024-11-22

### Added

- Initial release with 102 tools
- 5 documentation resources
- 1 deployment prompt
- Docker support with multi-stage build
- MCP protocol 2024-11-05 compliance

### Features

- Complete Forge API coverage for:
    - Servers management
    - Sites management
    - Deployments
    - SSL Certificates
    - Databases and users
    - Scheduled jobs
    - Daemons
    - Queue workers
    - Firewall rules
    - SSH keys
    - Webhooks
    - Redirect rules
    - Security rules
    - Nginx templates
    - Backup configurations
    - Monitoring
    - Recipes
    - Laravel integrations (Horizon, Octane, Reverb, Pulse, Inertia)

[Unreleased]: https://github.com/ddrcn/forge-mcp/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/ddrcn/forge-mcp/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/ddrcn/forge-mcp/releases/tag/v1.0.0
