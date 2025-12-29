<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Composite;

use App\Integrations\Forge\ForgeClient;
use Carbon\Carbon;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class SSLExpirationCheckTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Check SSL certificate expiration across all servers and sites.

        **Optional Parameters:**
        - `days_threshold`: Alert for certificates expiring within N days (default: 30)
        - `server_id`: Limit check to a specific server

        Returns a report of all certificates with their expiration status.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'days_threshold' => ['sometimes', 'integer', 'min:1'],
            'server_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $daysThreshold = $request->integer('days_threshold', 30);
        $specificServerId = $request->integer('server_id');

        try {
            $servers = [];

            if ($specificServerId > 0) {
                $servers = [$client->servers()->get($specificServerId)];
            } else {
                $serversCollection = $client->servers()->list();
                $servers = $serversCollection->servers;
            }

            $results = [
                'expiring_soon' => [],
                'expired' => [],
                'healthy' => [],
                'errors' => [],
            ];

            $now = Carbon::now();
            $threshold = $now->copy()->addDays($daysThreshold);

            foreach ($servers as $server) {
                try {
                    $sitesCollection = $client->sites()->list($server->id);
                    $sites = $sitesCollection->sites;

                    foreach ($sites as $site) {
                        try {
                            $certificatesCollection = $client->certificates()->list($server->id, $site->id);
                            $certificates = $certificatesCollection->certificates;

                            foreach ($certificates as $cert) {
                                if (! $cert->active) {
                                    continue;
                                }

                                $certInfo = [
                                    'server_id' => $server->id,
                                    'server_name' => $server->name,
                                    'site_id' => $site->id,
                                    'site_domain' => $site->name,
                                    'certificate_id' => $cert->id,
                                    'domain' => $cert->domain,
                                    'type' => $cert->type,
                                    'expires_at' => $cert->expiresAt,
                                ];

                                if (! $cert->expiresAt) {
                                    $results['healthy'][] = $certInfo;

                                    continue;
                                }

                                $expiryDate = Carbon::parse($cert->expiresAt);
                                $daysUntilExpiry = $now->diffInDays($expiryDate, false);

                                $certInfo['days_until_expiry'] = $daysUntilExpiry;

                                if ($daysUntilExpiry < 0) {
                                    $results['expired'][] = $certInfo;
                                } elseif ($expiryDate->lte($threshold)) {
                                    $results['expiring_soon'][] = $certInfo;
                                } else {
                                    $results['healthy'][] = $certInfo;
                                }
                            }
                        } catch (Exception $e) {
                            $results['errors'][] = [
                                'server_id' => $server->id,
                                'site_id' => $site->id,
                                'error' => $e->getMessage(),
                            ];
                        }
                    }
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'server_id' => $server->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            usort($results['expiring_soon'], fn ($a, $b) => $a['days_until_expiry'] <=> $b['days_until_expiry']);

            return Response::text(json_encode([
                'success' => true,
                'threshold_days' => $daysThreshold,
                'summary' => [
                    'total_checked' => count($results['expired']) + count($results['expiring_soon']) + count($results['healthy']),
                    'expired' => count($results['expired']),
                    'expiring_soon' => count($results['expiring_soon']),
                    'healthy' => count($results['healthy']),
                    'errors' => count($results['errors']),
                ],
                'action_required' => count($results['expired']) > 0 || count($results['expiring_soon']) > 0,
                'expired_certificates' => $results['expired'],
                'expiring_soon_certificates' => $results['expiring_soon'],
                'healthy_certificates' => array_slice($results['healthy'], 0, 10),
                'errors' => $results['errors'],
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
            'days_threshold' => $schema->integer()
                ->description('Alert for certificates expiring within N days (default: 30)')
                ->min(1)
                ->default(30),
            'server_id' => $schema->integer()
                ->description('Limit check to a specific server')
                ->min(1),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
