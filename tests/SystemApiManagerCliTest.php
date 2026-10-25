<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiManagerCliTest extends TestCase
{
    public function testGetCapabilitiesReturnsBindingMetadata(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'get-capabilities',
            '--request-id',
            'manager-cap-001',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('manager-cap-001', $payload['request_id']);
        self::assertNull($payload['error']);
        self::assertSame('system.capabilities', $payload['result']['method']);
        self::assertSame('local-manager-ready', $payload['result']['data']['binding']['status']);
        self::assertSame('org.w4.System1', $payload['result']['data']['binding']['dbus']['name']);
        self::assertContains('Call', $payload['result']['data']['binding']['dbus']['methods']);
    }

    public function testCallDispatchesProfilesGet(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-profile-001',
            'method' => 'profiles.get',
            'params' => [
                'profile_id' => 'w4-os-home',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('profiles.get', $payload['result']['method']);
        self::assertSame('w4-os-home', $payload['result']['resource']['id']);
        self::assertSame('profile:show', $payload['result']['data']['command']);
    }

    public function testCallDispatchesDbusRuntimeDiagnosis(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-dbus-runtime-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['resource']['kind']);
        self::assertSame('local-system-api-runtime-contract', $payload['result']['resource']['provider']);
    }

    public function testCallDispatchesPackagesSearch(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-packages-search-001',
            'method' => 'packages.search',
            'params' => [
                'query' => 'php',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PACKAGES_SEARCH_OUTPUT' => "php8.4-cli - command-line interpreter for the PHP scripting language\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('packages.search', $payload['result']['method']);
        self::assertSame('package-search-result', $payload['result']['resource']['kind']);
        self::assertSame('php8.4-cli', $payload['result']['data']['items'][0]['name']);
    }

    public function testCallDispatchesPackagesPlan(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-packages-plan-001',
            'method' => 'packages.plan',
            'params' => [
                'action' => 'install',
                'selection' => [
                    ['name' => 'php8.4-cli', 'architecture' => 'amd64'],
                ],
                'conffile_policy' => 'keep-local',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PACKAGES_PLAN_JSON' => json_encode([
                'kind' => 'package-plan',
                'status' => 'ok',
                'backend' => 'apt-get',
                'action' => 'install',
                'plan_id' => 'manager-pkg-plan-001',
                'requested_packages' => [
                    ['name' => 'php8.4-cli', 'architecture' => 'amd64'],
                ],
                'installs' => [
                    ['name' => 'php8.4-cli', 'architecture' => 'amd64', 'installed_version' => null, 'target_version' => '8.4.4-1'],
                ],
                'upgrades' => [],
                'removals' => [],
                'conffile_policy' => 'keep-local',
            ], JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('packages.plan', $payload['result']['method']);
        self::assertSame('package-plan', $payload['result']['resource']['kind']);
        self::assertSame('manager-pkg-plan-001', $payload['result']['data']['plan_id']);
    }

    public function testCallDispatchesUpdatesCheck(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-updates-check-001',
            'method' => 'updates.check',
            'params' => [
                'refresh' => true,
                'limit' => 1,
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_UPDATES_REFRESH_STDOUT' => 'Hit:1 http://deb.debian.org/debian stable InRelease',
            'W4_SYSTEM_API_UPDATES_LIST_OUTPUT' => "Listing... Done\nphp8.4-cli/stable 8.4.4-1 amd64 [upgradable from: 8.4.3-1]\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('updates.check', $payload['result']['method']);
        self::assertSame('updates-check', $payload['result']['resource']['kind']);
        self::assertSame('updates-available', $payload['result']['data']['status']);
        self::assertTrue($payload['result']['data']['refresh_requested']);
    }

    public function testCallDispatchesUpdatesApply(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-updates-apply-001',
            'method' => 'updates.apply',
            'params' => [
                'operation_id' => 'manager-update-op-apply-001',
                'plan_hash' => 'placeholder',
                'idempotency_key' => 'manager-updates-apply-' . $suffix,
                'plan' => [
                    'update_plan_schema_version' => 1,
                    'kind' => 'update-plan',
                    'operation_id' => 'manager-update-op-apply-001',
                    'profile_id' => 'w4-os-home',
                    'source_version' => '1.0.0-lab',
                    'target_version' => '1.0.1-lab',
                    'repository_snapshot' => [
                        'id' => 'w4-main-2026-09-19T120000Z',
                        'channel' => 'testing',
                    ],
                    'package_changes' => [
                        'install' => ['w4-recovery-tools'],
                        'upgrade' => ['w4-home-meta'],
                        'remove' => [],
                        'counts' => [
                            'install' => 1,
                            'upgrade' => 1,
                            'remove' => 0,
                            'total' => 2,
                        ],
                    ],
                    'execution' => [
                        'engine' => 'apt-offline-snapshot',
                        'apply_mode' => 'live-apt',
                        'reboot_required' => true,
                        'stages' => [
                            'planned',
                            'downloading',
                            'ready',
                            'prepared',
                            'applying_offline',
                            'pending_health',
                            'confirmed',
                        ],
                        'failure_state' => 'failed',
                    ],
                    'snapshot' => [
                        'root_subvolume' => '/@',
                        'snapshot_name' => 'pre-update-manager-update-op-apply-001',
                        'snapshot_path' => '/.snapshots/pre-update-manager-update-op-apply-001',
                        'retention' => 'protected-until-confirmed',
                    ],
                    'staging' => [
                        'directory' => '/var/lib/w4-update/staging/manager-update-op-apply-001',
                        'estimated_space_kib' => 262144,
                        'cache_policy' => 'protected-until-confirmed',
                    ],
                    'boot_manifest' => [
                        'kernel_package' => 'linux-image-amd64',
                        'boot_mode' => 'efi',
                        'initramfs_strategy' => 'update-initramfs',
                    ],
                    'health_check_manifest' => [
                        'required' => ['boot-entry-present', 'dpkg-consistent'],
                        'test_file_path' => '/var/lib/w4-update/test-file',
                        'pending_state' => 'pending_health',
                        'success_state' => 'confirmed',
                        'failure_state' => 'failed',
                    ],
                    'durable_state_template' => [
                        'update_operation_schema_version' => 1,
                        'kind' => 'update-operation',
                        'operation_id' => 'manager-update-op-apply-001',
                        'stage' => 'planned',
                        'last_error' => null,
                    ],
                    'evidence' => [
                        'update-plan.json',
                        'operation.json',
                    ],
                    'notes' => [
                        'Una sola operacion mutante puede estar activa a la vez.',
                    ],
                ],
            ],
        ];
        $request['params']['plan_hash'] = sha1(json_encode($request['params']['plan'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE' => '0',
            'W4_SYSTEM_API_UPDATES_APPLY_STDOUT' => 'Manager update apply',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('updates.apply', $payload['result']['method']);
        self::assertSame('pending-reboot', $payload['result']['data']['status']);
        self::assertSame('needs_attention', $payload['result']['data']['state']);
        self::assertSame('manager-update-op-apply-001', $payload['result']['data']['operation_id']);
    }

    public function testCallDispatchesPackagesApplyWithExpectedCatalogRevision(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-packages-apply-revision-001',
            'method' => 'packages.apply',
            'params' => [
                'plan_id' => 'manager-pkg-apply-001',
                'plan_hash' => 'placeholder',
                'idempotency_key' => 'manager-pkg-intent-revision-001',
                'expected_catalog_revision' => 'manager-catalog-001',
                'plan' => [
                    'kind' => 'package-plan',
                    'status' => 'ok',
                    'backend' => 'apt-get',
                    'action' => 'install',
                    'plan_id' => 'manager-pkg-apply-001',
                    'catalog_revision' => 'manager-catalog-001',
                    'requested_packages' => [
                        ['name' => 'php8.4-cli', 'architecture' => 'amd64'],
                    ],
                    'installs' => [
                        ['name' => 'php8.4-cli', 'architecture' => 'amd64', 'installed_version' => null, 'target_version' => '8.4.4-1'],
                    ],
                    'upgrades' => [],
                    'removals' => [],
                    'conffile_policy' => 'keep-local',
                ],
            ],
        ];
        $request['params']['plan']['plan_hash'] = sha1(json_encode($request['params']['plan'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $request['params']['plan_hash'] = $request['params']['plan']['plan_hash'];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '0',
            'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT' => 'Packages applied from manager',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('packages.apply', $payload['result']['method']);
        self::assertSame('manager-catalog-001', $payload['result']['data']['expected_catalog_revision']);
        self::assertSame('manager-catalog-001', $payload['result']['data']['observed_catalog_revision']);
    }

    public function testCallDispatchesServicesEnable(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-services-enable-001',
            'method' => 'services.enable',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_ENABLE_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('services.enable', $payload['result']['method']);
        self::assertSame('service-operation', $payload['result']['resource']['kind']);
        self::assertSame('enable', $payload['result']['data']['action']);
        self::assertSame('succeeded', $payload['result']['data']['state']);
    }

    public function testCallDispatchesOperationsGet(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-services-restart-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $operationResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $operationResult['exitCode'], $operationResult['stderr']);
        $operationPayload = $this->decodeJson($operationResult['stdout']);
        $operationId = $operationPayload['result']['operation_id'];
        self::assertIsString($operationId);

        $getRequest = [
            'api_version' => '1.0',
            'request_id' => 'manager-operations-get-001',
            'method' => 'operations.get',
            'params' => [
                'operation_id' => $operationId,
            ],
        ];

        $getResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($getRequest, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $getResult['exitCode'], $getResult['stderr']);

        $payload = $this->decodeJson($getResult['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('operations.get', $payload['result']['method']);
        self::assertSame('system-api-operation', $payload['result']['resource']['kind']);
        self::assertSame('services.restart', $payload['result']['data']['method']);
        self::assertSame('succeeded', $payload['result']['data']['state']);
    }

    public function testCallDispatchesOperationsCancel(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-services-disable-001',
            'method' => 'services.disable',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $operationResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_DISABLE_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=inactive\nSubState=dead\nUnitFileState=disabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $operationResult['exitCode'], $operationResult['stderr']);
        $operationPayload = $this->decodeJson($operationResult['stdout']);
        $operationId = $operationPayload['result']['operation_id'];
        self::assertIsString($operationId);

        $cancelRequest = [
            'api_version' => '1.0',
            'request_id' => 'manager-operations-cancel-001',
            'method' => 'operations.cancel',
            'params' => [
                'operation_id' => $operationId,
            ],
        ];

        $cancelResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($cancelRequest, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $cancelResult['exitCode'], $cancelResult['stderr']);

        $payload = $this->decodeJson($cancelResult['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('operations.cancel', $payload['result']['method']);
        self::assertSame('system-api-operation-cancel', $payload['result']['resource']['kind']);
        self::assertFalse($payload['result']['data']['cancel_requested']);
    }

    public function testCallDispatchesServicesStartWithIdempotencyKey(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-services-start-idempotent-001',
            'method' => 'services.start',
            'params' => [
                'unit' => 'ssh.service',
                'idempotency_key' => 'manager-svc-start-' . $suffix,
            ],
        ];

        $firstResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_START_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_START_STDOUT' => 'service started',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $firstResult['exitCode'], $firstResult['stderr']);
        $firstPayload = $this->decodeJson($firstResult['stdout']);
        self::assertNull($firstPayload['error']);

        $secondResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_START_EXIT_CODE' => '1',
            'W4_SYSTEM_API_SERVICES_START_STDERR' => 'No deberia ejecutarse otra vez',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $secondResult['exitCode'], $secondResult['stderr']);
        $secondPayload = $this->decodeJson($secondResult['stdout']);
        self::assertNull($secondPayload['error']);
        self::assertSame($firstPayload['result']['operation_id'], $secondPayload['result']['operation_id']);
        self::assertSame('service started', $secondPayload['result']['data']['stdout']);
    }

    public function testCallDispatchesServicesRestartWithExpectedRevision(): void
    {
        $statusRequest = [
            'api_version' => '1.0',
            'request_id' => 'manager-services-status-revision-001',
            'method' => 'services.status',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $statusResult = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($statusRequest, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $statusResult['exitCode'], $statusResult['stderr']);
        $statusPayload = $this->decodeJson($statusResult['stdout']);
        $expectedRevision = $statusPayload['result']['data']['revision'];
        self::assertIsString($expectedRevision);

        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-services-restart-revision-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
                'expected_revision' => $expectedRevision,
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('services.restart', $payload['result']['method']);
        self::assertSame($expectedRevision, $payload['result']['data']['expected_revision']);
        self::assertSame($expectedRevision, $payload['result']['data']['observed_revision_before']);
    }

    public function testCallReturnsProtocolErrorForInvalidJson(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            '{bad json}',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('invalid-request', $payload['request_id']);
        self::assertNull($payload['result']);
        self::assertSame('VALIDATION_ERROR', $payload['error']['code']);
        self::assertStringContainsString('Request JSON invalido', $payload['error']['message']);
    }

    public function testCallViaRequestFileWritesOutput(): void
    {
        $requestPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-manager-request-' . bin2hex(random_bytes(6)) . '.json';
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-manager-output-' . bin2hex(random_bytes(6)) . '.json';

        try {
            file_put_contents(
                $requestPath,
                json_encode([
                    'api_version' => '1.0',
                    'request_id' => 'manager-update-001',
                    'method' => 'updates.plan',
                    'params' => [
                        'request_path' => 'examples/update/home-lab-to-1.0.1.update-request.json',
                        'operation_id' => 'manager-update-op-001',
                        'output_path' => $outputPath,
                    ],
                ], JSON_THROW_ON_ERROR)
            );

            $result = $this->runPhpScript([
                $this->scriptPath(),
                'call',
                '--request-file',
                $requestPath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.plan', $payload['result']['method']);
            self::assertSame('manager-update-op-001', $payload['result']['data']['operation_id']);

            $plan = $this->decodeJsonFile($outputPath);
            self::assertSame('update-plan', $plan['kind']);
            self::assertSame('manager-update-op-001', $plan['operation_id']);
        } finally {
            if (is_file($requestPath)) {
                unlink($requestPath);
            }

            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command, array $environment = []): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $processEnvironment = array_merge($_ENV, $environment);
        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__), $processEnvironment);
        self::assertIsResource($process, 'No se pudo ejecutar system_api_manager.php');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $this->decodeJson($raw);
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_manager.php';
    }
}
