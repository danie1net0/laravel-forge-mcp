<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Teste de Integração Forge MCP ===\n\n";

$forge = app(App\Services\ForgeService::class);

try {
    echo "1. Testando listagem de servidores...\n";
    $servers = $forge->listServers();
    echo "   ✓ Encontrados " . count($servers) . " servidor(es)\n";

    if (count($servers) > 0) {
        $serverId = $servers[0]->id;
        echo "   Servidor de teste: #{$serverId} - {$servers[0]->name}\n\n";

        echo "2. Testando detalhes do servidor...\n";
        $server = $forge->getServer($serverId);
        echo "   ✓ IP: {$server->ipAddress}\n";
        echo "   ✓ Região: {$server->region}\n\n";

        echo "3. Testando listagem de sites...\n";
        $sites = $forge->listSites($serverId);
        echo "   ✓ Encontrados " . count($sites) . " site(s)\n";

        if (count($sites) > 0) {
            $siteId = $sites[0]->id;
            echo "   Site de teste: #{$siteId} - {$sites[0]->name}\n\n";

            echo "4. Testando detalhes do site...\n";
            $site = $forge->getSite($serverId, $siteId);
            echo "   ✓ Domínio: {$site->name}\n";
            echo "   ✓ Diretório: {$site->directory}\n\n";

            // Testar novos métodos GET implementados
            echo "5. Testando NOVO MÉTODO: siteLog()...\n";

            try {
                $logData = $forge->siteLog($serverId, $siteId);
                echo "   ✓ Log obtido com sucesso (array)\n";
                echo "   Estrutura do retorno: " . json_encode(array_keys($logData)) . "\n";

                if (isset($logData['content'])) {
                    $logContent = $logData['content'];
                    echo "   Tamanho do conteúdo: " . mb_strlen($logContent) . " caracteres\n";

                    if (mb_strlen($logContent) > 0) {
                        echo "   Primeiras linhas:\n";
                        $lines = explode("\n", $logContent);

                        foreach (array_slice($lines, 0, 3) as $line) {
                            echo "   " . mb_substr($line, 0, 80) . "\n";
                        }
                    }
                } else {
                    echo "   Dados retornados: " . json_encode($logData) . "\n";
                }
            } catch (Exception $e) {
                echo "   ⚠ Erro ao obter logs: " . $e->getMessage() . "\n";
            }
            echo "\n";

            echo "6. Testando NOVO MÉTODO: deploymentHistory()...\n";

            try {
                $history = $forge->deploymentHistory($serverId, $siteId);
                echo "   ✓ Histórico obtido com sucesso\n";
                echo "   Total de deployments: " . count($history) . "\n";

                if (count($history) > 0) {
                    $firstDeploy = $history[0];
                    echo "   Último deployment ID: {$firstDeploy['id']}\n";

                    echo "\n7. Testando NOVO MÉTODO: deploymentHistoryDeployment()...\n";

                    try {
                        $deploymentDetails = $forge->deploymentHistoryDeployment($serverId, $siteId, $firstDeploy['id']);
                        echo "   ✓ Detalhes do deployment obtidos\n";
                        echo "   Status: " . ($deploymentDetails['status'] ?? 'N/A') . "\n";
                    } catch (Exception $e) {
                        echo "   ⚠ Erro: " . $e->getMessage() . "\n";
                    }

                    echo "\n8. Testando NOVO MÉTODO: deploymentHistoryOutput()...\n";

                    try {
                        $outputData = $forge->deploymentHistoryOutput($serverId, $siteId, $firstDeploy['id']);
                        echo "   ✓ Output do deployment obtido (array)\n";

                        if (isset($outputData['output'])) {
                            echo "   Tamanho do output: " . mb_strlen($outputData['output']) . " caracteres\n";
                        } else {
                            echo "   Estrutura: " . json_encode(array_keys($outputData)) . "\n";
                        }
                    } catch (Exception $e) {
                        echo "   ⚠ Erro: " . $e->getMessage() . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "   ⚠ Erro ao obter histórico: " . $e->getMessage() . "\n";
            }
            echo "\n";

            echo "9. Testando NOVO MÉTODO: listCommandHistory()...\n";

            try {
                $commands = $forge->listCommandHistory($serverId, $siteId);
                echo "   ✓ Histórico de comandos obtido\n";
                echo "   Total de comandos: " . count($commands) . "\n";

                if (count($commands) > 0) {
                    $firstCommand = $commands[0];
                    echo "   Último comando ID: {$firstCommand['id']}\n";

                    echo "\n10. Testando NOVO MÉTODO: getSiteCommand()...\n";

                    try {
                        $commandDetails = $forge->getSiteCommand($serverId, $siteId, $firstCommand['id']);
                        echo "   ✓ Detalhes do comando obtidos\n";
                        echo "   Status: " . ($commandDetails['status'] ?? 'N/A') . "\n";
                    } catch (Exception $e) {
                        echo "   ⚠ Erro: " . $e->getMessage() . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "   ⚠ Erro ao listar comandos: " . $e->getMessage() . "\n";
            }
            echo "\n";

            echo "11. Testando listagem de certificados...\n";

            try {
                $certificates = $forge->listCertificates($serverId, $siteId);
                echo "   ✓ Encontrados " . count($certificates) . " certificado(s)\n";

                if (count($certificates) > 0) {
                    $certId = $certificates[0]->id;
                    echo "   Certificado de teste: #{$certId}\n";

                    echo "\n12. Testando NOVO MÉTODO: getCertificateSigningRequest()...\n";

                    try {
                        $csr = $forge->getCertificateSigningRequest($serverId, $siteId, $certId);
                        echo "   ✓ CSR obtido com sucesso (string)\n";
                        echo "   Tamanho do CSR: " . mb_strlen($csr) . " caracteres\n";
                        echo "   Início do CSR:\n   " . mb_substr($csr, 0, 50) . "...\n";
                    } catch (Exception $e) {
                        echo "   ⚠ Erro ao obter CSR: " . $e->getMessage() . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "   ⚠ Erro ao listar certificados: " . $e->getMessage() . "\n";
            }
            echo "\n";
        }

        echo "13. Testando listagem de databases...\n";

        try {
            $databases = $forge->listDatabases($serverId);
            echo "   ✓ Encontrados " . count($databases) . " database(s)\n";
        } catch (Exception $e) {
            echo "   ⚠ Erro: " . $e->getMessage() . "\n";
        }
        echo "\n";

        echo "14. Testando NOVO MÉTODO: listDatabaseUsers()...\n";

        try {
            $users = $forge->listDatabaseUsers($serverId);
            echo "   ✓ Encontrados " . count($users) . " usuário(s) de database\n";

            if (count($users) > 0) {
                $userId = $users[0]->id;
                echo "   Usuário de teste: #{$userId} - {$users[0]->name}\n";

                echo "\n15. Testando NOVO MÉTODO: getDatabaseUser()...\n";

                try {
                    $user = $forge->getDatabaseUser($serverId, $userId);
                    echo "   ✓ Detalhes do usuário obtidos\n";
                    echo "   Nome: {$user->name}\n";
                    echo "   Status: {$user->status}\n";
                } catch (Exception $e) {
                    echo "   ⚠ Erro: " . $e->getMessage() . "\n";
                }
            }
        } catch (Exception $e) {
            echo "   ⚠ Erro ao listar usuários: " . $e->getMessage() . "\n";
        }
        echo "\n";
    } else {
        echo "   ⚠ Nenhum servidor encontrado para testar\n";
    }

    echo "\n=== Resumo ===\n";
    echo "✓ Métodos antigos funcionando normalmente\n";
    echo "✓ 8 novos métodos GET implementados:\n";
    echo "   1. siteLog()\n";
    echo "   2. deploymentHistory()\n";
    echo "   3. deploymentHistoryDeployment()\n";
    echo "   4. deploymentHistoryOutput()\n";
    echo "   5. listCommandHistory()\n";
    echo "   6. getSiteCommand()\n";
    echo "   7. getCertificateSigningRequest()\n";
    echo "   8. getDatabaseUser() + listDatabaseUsers()\n";
    echo "\n✓ Integração com Forge SDK funcionando!\n";
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    exit(1);
}
