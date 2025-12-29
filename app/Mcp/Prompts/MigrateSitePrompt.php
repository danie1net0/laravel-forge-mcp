<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class MigrateSitePrompt extends Prompt
{
    protected string $name = 'migrate-site';

    protected string $description = 'Complete site migration workflow between Forge servers';

    public function handle(Request $request): array
    {
        $sourceServerId = $request->string('source_server_id', '');
        $targetServerId = $request->string('target_server_id', '');
        $siteDomain = $request->string('site_domain', '');
        $includeDatabase = $request->boolean('include_database', true);

        $workflow = "# Site Migration Workflow\n\n";

        if ($sourceServerId->isEmpty() || $targetServerId->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 1: Identify Servers

            1. Use `list-servers-tool` to list all available servers
            2. Identify the SOURCE server (current location)
            3. Identify the TARGET server (new location)
            4. Verify both servers are accessible and "installed" status

            MD;
        }

        if ($siteDomain->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 2: Identify Site

            1. Use `list-sites-tool` on the source server
            2. Identify the site to migrate
            3. Use `get-site-tool` to get complete site configuration

            MD;
        }

        $workflow .= <<<'MD'
        ## Step 3: Pre-Migration Checklist

        1. Note current site configuration:
           - PHP version
           - Web directory
           - Git repository URL
           - Environment variables
           - SSL certificate type

        2. Check for dependencies:
           - Use `list-workers-tool` to note queue workers
           - Use `list-scheduled-jobs-tool` to note cron jobs
           - Use `list-daemons-tool` to note running daemons

        ## Step 4: Create Site on Target Server

        3. Use `create-site-tool` on target server with same configuration:
           - Same domain
           - Same PHP version
           - Same web directory

        4. Use `install-git-repository-tool` to connect repository

        5. Use `get-deployment-script-tool` from source and
           `update-deployment-script-tool` on target to match scripts

        MD;

        if ($includeDatabase) {
            $workflow .= <<<'MD'

            ## Step 5: Database Migration

            6. Use `list-databases-tool` on source to identify database
            7. Use `create-database-tool` on target with same name
            8. Use `create-database-user-tool` to create user with same permissions

            9. Export database from source server (via SSH or recipe):
               ```bash
               mysqldump -u forge -p database_name > backup.sql
               ```

            10. Import database on target server:
                ```bash
                mysql -u forge -p database_name < backup.sql
                ```

            MD;
        }

        $workflow .= <<<'MD'

        ## Step 6: Environment Configuration

        11. Use `get-env-tool` on source to get environment variables
        12. Use `update-env-tool` on target with updated values:
            - Update database credentials
            - Update any server-specific values
            - Keep API keys and secrets the same

        ## Step 7: SSL Certificate

        13. Use `obtain-lets-encrypt-certificate-tool` on target
            OR use `install-certificate-tool` to copy existing certificate

        ## Step 8: Workers and Jobs

        14. Use `create-worker-tool` on target to recreate workers
        15. Use `create-scheduled-job-tool` on target to recreate cron jobs
        16. Use `create-daemon-tool` on target for any daemons

        ## Step 9: Deploy and Test

        17. Use `deploy-site-tool` on target to deploy the application
        18. Use `get-deployment-log-tool` to verify successful deployment
        19. Test the site on target server using temporary URL or hosts file

        ## Step 10: DNS Cutover

        20. Update DNS records to point to target server IP
        21. Wait for DNS propagation (typically 5 minutes to 24 hours)
        22. Monitor both servers during transition

        ## Step 11: Cleanup (After Verification)

        23. Disable cron jobs on source: `delete-scheduled-job-tool`
        24. Stop workers on source: `delete-worker-tool`
        25. Keep source site as backup for 24-48 hours
        26. Delete source site when confident: `delete-site-tool`

        ## Rollback Plan

        If migration fails:
        1. Keep source site running
        2. Revert DNS to source server
        3. Investigate and fix issues on target
        4. Retry migration

        MD;

        return [Response::text($workflow)->asAssistant()];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'source_server_id',
                description: 'Source server ID (current location)',
                required: false,
            ),
            new Argument(
                name: 'target_server_id',
                description: 'Target server ID (new location)',
                required: false,
            ),
            new Argument(
                name: 'site_domain',
                description: 'Domain of the site to migrate',
                required: false,
            ),
            new Argument(
                name: 'include_database',
                description: 'Include database migration steps (default: true)',
                required: false,
            ),
        ];
    }
}
