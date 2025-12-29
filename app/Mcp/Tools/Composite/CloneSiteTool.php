<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Composite;

use App\Integrations\Forge\Data\Certificates\ObtainLetsEncryptCertificateData;
use App\Integrations\Forge\Data\Jobs\CreateJobData;
use App\Integrations\Forge\Data\Sites\{CreateSiteData, InstallGitRepositoryData};
use App\Integrations\Forge\Data\Workers\CreateWorkerData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class CloneSiteTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Clone a site configuration to a new domain on the same or different server.

        **Required Parameters:**
        - `source_server_id`: The source server ID
        - `source_site_id`: The source site ID
        - `target_server_id`: The target server ID (can be same as source)
        - `new_domain`: The domain for the new site

        **Optional Parameters:**
        - `clone_workers`: Clone queue workers (default: true)
        - `clone_jobs`: Clone scheduled jobs (default: true)
        - `clone_ssl`: Obtain SSL certificate for new site (default: true)
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'source_server_id' => ['required', 'integer', 'min:1'],
            'source_site_id' => ['required', 'integer', 'min:1'],
            'target_server_id' => ['required', 'integer', 'min:1'],
            'new_domain' => ['required', 'string'],
            'clone_workers' => ['sometimes', 'boolean'],
            'clone_jobs' => ['sometimes', 'boolean'],
            'clone_ssl' => ['sometimes', 'boolean'],
        ]);

        $sourceServerId = $request->integer('source_server_id');
        $sourceSiteId = $request->integer('source_site_id');
        $targetServerId = $request->integer('target_server_id');
        $newDomain = $request->string('new_domain')->value();
        $cloneWorkers = $request->boolean('clone_workers', true);
        $cloneJobs = $request->boolean('clone_jobs', true);
        $cloneSsl = $request->boolean('clone_ssl', true);

        $steps = [];

        try {
            $sourceSite = $client->sites()->get($sourceServerId, $sourceSiteId);
            $steps[] = ['action' => 'get_source_site', 'status' => 'success', 'message' => "Found source site: {$sourceSite->name}"];

            $siteData = CreateSiteData::from([
                'domain' => $newDomain,
                'project_type' => $sourceSite->projectType ?? 'php',
                'directory' => $sourceSite->directory ?? '/public',
                'php_version' => $sourceSite->phpVersion ?? 'php84',
            ]);

            $newSite = $client->sites()->create($targetServerId, $siteData);
            $steps[] = ['action' => 'create_site', 'status' => 'success', 'message' => "Created new site: {$newDomain}", 'site_id' => $newSite->id];

            if ($sourceSite->repository) {
                try {
                    $repoData = InstallGitRepositoryData::from([
                        'provider' => $sourceSite->repositoryProvider ?? 'github',
                        'repository' => $sourceSite->repository,
                        'branch' => $sourceSite->repositoryBranch ?? 'main',
                    ]);
                    $client->sites()->installGitRepository($targetServerId, $newSite->id, $repoData);
                    $steps[] = ['action' => 'install_git', 'status' => 'success', 'message' => "Installed git repository: {$sourceSite->repository}"];
                } catch (Exception $e) {
                    $steps[] = ['action' => 'install_git', 'status' => 'failed', 'message' => $e->getMessage()];
                }
            }

            try {
                $deploymentScript = $client->sites()->deploymentScript($sourceServerId, $sourceSiteId);
                $updatedScript = str_replace($sourceSite->name, $newDomain, $deploymentScript);
                $client->sites()->updateDeploymentScript($targetServerId, $newSite->id, $updatedScript);
                $steps[] = ['action' => 'copy_deployment_script', 'status' => 'success', 'message' => 'Deployment script copied and updated'];
            } catch (Exception $e) {
                $steps[] = ['action' => 'copy_deployment_script', 'status' => 'failed', 'message' => $e->getMessage()];
            }

            if ($cloneWorkers) {
                try {
                    $workersCollection = $client->workers()->list($sourceServerId, $sourceSiteId);
                    $workers = $workersCollection->workers;

                    foreach ($workers as $worker) {
                        $workerData = CreateWorkerData::from([
                            'connection' => $worker->connection,
                            'queue' => $worker->queue,
                            'timeout' => $worker->timeout ?? 60,
                            'sleep' => $worker->sleep ?? 3,
                            'processes' => $worker->processes ?? 1,
                        ]);
                        $client->workers()->create($targetServerId, $newSite->id, $workerData);
                    }
                    $steps[] = ['action' => 'clone_workers', 'status' => 'success', 'message' => 'Cloned ' . count($workers) . ' workers'];
                } catch (Exception $e) {
                    $steps[] = ['action' => 'clone_workers', 'status' => 'failed', 'message' => $e->getMessage()];
                }
            }

            if ($cloneJobs) {
                try {
                    $jobsCollection = $client->jobs()->list($sourceServerId);
                    $jobs = $jobsCollection->jobs;
                    $siteJobs = array_filter($jobs, fn ($job) => str_contains($job->command, $sourceSite->name));

                    foreach ($siteJobs as $job) {
                        $jobData = CreateJobData::from([
                            'command' => str_replace($sourceSite->name, $newDomain, $job->command),
                            'frequency' => $job->frequency,
                            'user' => $job->user ?? 'forge',
                        ]);
                        $client->jobs()->create($targetServerId, $jobData);
                    }
                    $steps[] = ['action' => 'clone_jobs', 'status' => 'success', 'message' => 'Cloned ' . count($siteJobs) . ' scheduled jobs'];
                } catch (Exception $e) {
                    $steps[] = ['action' => 'clone_jobs', 'status' => 'failed', 'message' => $e->getMessage()];
                }
            }

            if ($cloneSsl) {
                try {
                    $certData = ObtainLetsEncryptCertificateData::from(['domains' => [$newDomain]]);
                    $client->certificates()->obtainLetsEncrypt($targetServerId, $newSite->id, $certData);
                    $steps[] = ['action' => 'obtain_ssl', 'status' => 'success', 'message' => "SSL certificate requested for {$newDomain}"];
                } catch (Exception $e) {
                    $steps[] = ['action' => 'obtain_ssl', 'status' => 'failed', 'message' => $e->getMessage()];
                }
            }

            $failedSteps = array_filter($steps, fn ($s) => $s['status'] === 'failed');

            return Response::text(json_encode([
                'success' => count($failedSteps) === 0,
                'new_site' => [
                    'server_id' => $targetServerId,
                    'site_id' => $newSite->id,
                    'domain' => $newDomain,
                ],
                'source_site' => [
                    'server_id' => $sourceServerId,
                    'site_id' => $sourceSiteId,
                    'domain' => $sourceSite->name,
                ],
                'steps' => $steps,
                'next_steps' => [
                    'Update environment variables with update-env-tool',
                    'Create database if needed with create-database-tool',
                    'Deploy the site with deploy-site-tool',
                    'Update DNS records to point to target server',
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'steps_completed' => $steps,
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'source_server_id' => $schema->integer()
                ->description('The source server ID')
                ->min(1)
                ->required(),
            'source_site_id' => $schema->integer()
                ->description('The source site ID')
                ->min(1)
                ->required(),
            'target_server_id' => $schema->integer()
                ->description('The target server ID')
                ->min(1)
                ->required(),
            'new_domain' => $schema->string()
                ->description('The domain for the new site')
                ->required(),
            'clone_workers' => $schema->boolean()
                ->description('Clone queue workers (default: true)')
                ->default(true),
            'clone_jobs' => $schema->boolean()
                ->description('Clone scheduled jobs (default: true)')
                ->default(true),
            'clone_ssl' => $schema->boolean()
                ->description('Obtain SSL certificate for new site (default: true)')
                ->default(true),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
