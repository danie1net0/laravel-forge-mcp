<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Composite;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class SiteStatusDashboardTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Comprehensive site status dashboard with all related information.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site

        Returns a complete dashboard including:
        - Site configuration and status
        - SSL certificate status
        - Recent deployments
        - Active workers
        - Scheduled jobs
        - Environment summary
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $site = $client->sites()->get($serverId, $siteId);

            $certificates = [];

            try {
                $certsCollection = $client->certificates()->list($serverId, $siteId);
                $certificates = $certsCollection->certificates;
            } catch (Exception) {
            }

            $deployments = [];

            try {
                $deployments = $client->sites()->deploymentHistory($serverId, $siteId);
            } catch (Exception) {
            }

            $workers = [];

            try {
                $workersCollection = $client->workers()->list($serverId, $siteId);
                $workers = $workersCollection->workers;
            } catch (Exception) {
            }

            $jobs = [];

            try {
                $jobsCollection = $client->jobs()->list($serverId);
                $jobs = $jobsCollection->jobs;
            } catch (Exception) {
            }

            $siteJobs = array_filter($jobs, fn ($job) => str_contains($job->command, $site->name));

            $activeCert = null;

            foreach ($certificates as $cert) {
                if ($cert->active) {
                    $activeCert = $cert;
                    break;
                }
            }

            $sslStatus = 'none';
            $sslExpiry = null;

            if ($activeCert) {
                $sslStatus = 'active';
                $sslExpiry = $activeCert->expiresAt ?? null;
            }

            $lastDeployment = count($deployments) > 0 ? $deployments[0] : null;

            return Response::text(json_encode([
                'success' => true,
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'status' => $site->status,
                    'directory' => $site->directory,
                    'php_version' => $site->phpVersion,
                    'quick_deploy' => $site->quickDeploy,
                    'created_at' => $site->createdAt,
                ],
                'repository' => $site->repository ? [
                    'provider' => $site->repositoryProvider,
                    'repository' => $site->repository,
                    'branch' => $site->repositoryBranch,
                ] : null,
                'ssl' => [
                    'status' => $sslStatus,
                    'expires_at' => $sslExpiry,
                    'total_certificates' => count($certificates),
                ],
                'deployment' => $lastDeployment ? [
                    'id' => $lastDeployment->id,
                    'status' => $lastDeployment->status,
                    'ended_at' => $lastDeployment->endedAt,
                ] : null,
                'workers' => [
                    'total' => count($workers),
                    'list' => array_map(fn ($w) => [
                        'id' => $w->id,
                        'connection' => $w->connection,
                        'queue' => $w->queue,
                        'status' => $w->status,
                    ], $workers),
                ],
                'scheduled_jobs' => [
                    'total' => count($siteJobs),
                ],
                'recent_deployments' => array_map(fn ($d) => [
                    'id' => $d->id,
                    'status' => $d->status,
                    'ended_at' => $d->endedAt,
                ], array_slice($deployments, 0, 5)),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
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
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
