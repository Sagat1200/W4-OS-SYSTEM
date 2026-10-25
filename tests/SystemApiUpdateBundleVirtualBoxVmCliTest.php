<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiUpdateBundleVirtualBoxVmCliTest extends TestCase
{
    private string $rootDir;
    /** @var list<string> */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        putenv('W4_VBOXMANAGE_PATH=C:\VBox\VBoxManage.exe');
    }

    protected function tearDown(): void
    {
        putenv('W4_VBOXMANAGE_PATH');
        putenv('W4_SYSTEM_API_UPDATE_VM_COMMAND_MAP_JSON');

        foreach (array_reverse($this->tempPaths) as $path) {
            $this->removePath($path);
        }

        $this->tempPaths = [];
    }

    public function testCheckOnlyReturnsCanonicalReadyPayload(): void
    {
        $storeDir = $this->createBundleStore('virtualbox-ready');
        $this->setVmInfoFixture('W4-OS-Server-DbusModern', 'poweroff');

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_system_api_update_bundle_via_virtualbox_vm.php'),
            [
                '--vm-name',
                'W4-OS-Server-DbusModern',
                '--store-dir',
                $storeDir,
                '--ssh-port',
                '2244',
                '--stage',
                'health',
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ready', $payload['status']);
        self::assertSame('W4-OS-Server-DbusModern', $payload['vm_name']);
        self::assertSame('C:\VBox\VBoxManage.exe', $payload['vboxmanage_path']);
        self::assertSame('poweroff', $payload['vm_info']['state']);
        self::assertSame('W4-OS-Server-DbusModern', $payload['vm_info']['name']);
        self::assertSame($storeDir, $payload['store_dir']);
        self::assertSame('health', $payload['stage']);
        self::assertSame(2244, $payload['ssh_port']);
        self::assertSame('w4live', $payload['username']);
        self::assertStringEndsWith('/' . basename($storeDir), $payload['remote_bundle_dir']);
        self::assertStringContainsString('./run-health-checks.sh', $payload['remote_command']);
        self::assertStringContainsString('source ./executor.env', $payload['remote_command']);
        self::assertContains('executor.env', $payload['required_artifacts']);
        self::assertContains('reconcile-update-operation.php', $payload['required_artifacts']);
        self::assertStringEndsWith('scripts\enable_live_ssh_in_virtualbox.php', $payload['enable_live_ssh_script']);
        self::assertStringEndsWith('scripts\upload_tree_via_paramiko.php', $payload['upload_tree_script']);
        self::assertStringEndsWith('scripts\run_remote_command_via_paramiko.php', $payload['run_remote_command_script']);
        self::assertStringEndsWith('scripts\download_files_via_paramiko.php', $payload['download_files_script']);
        self::assertContains('operation.json', $payload['sync_back_artifacts']);
        self::assertContains('health-check-results.json', $payload['sync_back_artifacts']);
    }

    public function testExecutionReturnsCanonicalOkPayload(): void
    {
        $storeDir = $this->createBundleStore('virtualbox-execution');
        $vmName = 'W4-OS-Server-DbusModern';
        $phpBinary = PHP_BINARY;
        $enableLiveSshScript = $this->scriptPath('scripts/enable_live_ssh_in_virtualbox.php');
        $uploadTreeScript = $this->scriptPath('scripts/upload_tree_via_paramiko.php');
        $runRemoteScript = $this->scriptPath('scripts/run_remote_command_via_paramiko.php');
        $downloadFilesScript = $this->scriptPath('scripts/download_files_via_paramiko.php');
        $remoteBundleDir = '/home/w4live/system-api-update-bundle/' . basename($storeDir);
        $remoteCommand = sprintf(
            'bash -lc %s',
            $this->quoteForBash(sprintf(
                'cd %s && %s && source ./executor.env && %s',
                $this->quoteForBashDouble($remoteBundleDir),
                'chmod +x "./run-update-offline.sh" "./run-update-with-repo-env.sh" "./run-health-checks.sh" "./reconcile-after-reboot.sh"',
                './run-health-checks.sh'
            ))
        );

        $fixtureMap = $this->createVmInfoFixtureMap($vmName, 'running') + [
            $this->fixtureKey([
                $phpBinary,
                $enableLiveSshScript,
                '--vm-name',
                $vmName,
                '--live-user',
                'w4live',
                '--live-password',
                'fixture-live-password',
                '--ssh-host',
                '127.0.0.1',
                '--ssh-port',
                '2244',
                '--wait-ms',
                '0',
            ]) => json_encode([
                'status' => 'ok',
                'vm_name' => $vmName,
                'ssh_host' => '127.0.0.1',
                'ssh_port' => 2244,
                'live_user' => 'w4live',
                'executed_steps' => [
                    'ensure_sshd_runtime_dirs',
                    'set_live_password',
                    'write_password_auth_override',
                    'allow_ssh_in_ufw',
                    'restart_ssh_service',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $this->fixtureKey([
                $phpBinary,
                $uploadTreeScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--local-dir',
                $storeDir,
                '--remote-dir',
                $remoteBundleDir,
                '--replace',
            ]) => 'uploaded',
            $this->fixtureKey([
                $phpBinary,
                $runRemoteScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--command-b64',
                base64_encode($remoteCommand),
                '--pty',
            ]) => 'health-checks-ok',
            $this->fixtureKey([
                $phpBinary,
                $downloadFilesScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--remote-dir',
                $remoteBundleDir,
                '--local-dir',
                $storeDir,
                '--artifact',
                'operation.json',
                '--artifact',
                'health-report.json',
                '--artifact',
                'events.ndjson',
                '--artifact',
                'health-check-results.json',
                '--artifact',
                'offline-application.json',
                '--artifact',
                'snapshot-manifest.json',
                '--artifact',
                'staging-manifest.json',
            ]) => '{"status":"ok","downloaded":["operation.json","health-report.json"],"missing":[]}',
        ];

        putenv('W4_SYSTEM_API_UPDATE_VM_COMMAND_MAP_JSON=' . json_encode($fixtureMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_system_api_update_bundle_via_virtualbox_vm.php'),
            [
                '--vm-name',
                $vmName,
                '--store-dir',
                $storeDir,
                '--ssh-port',
                '2244',
                '--stage',
                'health',
                '--live-password',
                'fixture-live-password',
                '--wait-ms',
                '0',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ok', $payload['status']);
        self::assertSame('running', $payload['vm_info']['state']);
        self::assertSame('health', $payload['stage']);
        self::assertSame($remoteBundleDir, $payload['remote_bundle_dir']);
        self::assertSame($remoteCommand, $payload['remote_command']);
        self::assertSame('uploaded', $payload['upload_output']);
        self::assertSame('health-checks-ok', $payload['execution_output']);
        self::assertSame(0, $payload['execution_exit_code']);
        self::assertSame('', $payload['execution_error_output']);
        self::assertSame('{"status":"ok","downloaded":["operation.json","health-report.json"],"missing":[]}', $payload['download_output']);
        self::assertSame('health', $payload['synced_artifact_validation']['validated_stage']);
        self::assertSame('ok', $payload['synced_artifact_validation']['health_status']);
        self::assertSame('confirmed', $payload['synced_artifact_validation']['expected_stage']);
        self::assertSame('reconcile', $payload['next_step']);

        $liveSshPayload = $this->decodeJson($payload['live_ssh_setup_output']);
        self::assertSame('ok', $liveSshPayload['status']);
        self::assertSame($vmName, $liveSshPayload['vm_name']);
    }

    public function testHealthStageFailsWhenSyncedHealthResultReportsFailure(): void
    {
        $storeDir = $this->createBundleStore('virtualbox-health-failed');
        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'health-check-results.json', [
            'health_check_result_schema_version' => 1,
            'kind' => 'health-check-results',
            'status' => 'failed',
            'results' => [
                'boot-entry-present' => 'failed',
                'dpkg-consistent' => 'ok',
            ],
        ]);

        $vmName = 'W4-OS-Server-DbusModern';
        $phpBinary = PHP_BINARY;
        $uploadTreeScript = $this->scriptPath('scripts/upload_tree_via_paramiko.php');
        $runRemoteScript = $this->scriptPath('scripts/run_remote_command_via_paramiko.php');
        $downloadFilesScript = $this->scriptPath('scripts/download_files_via_paramiko.php');
        $remoteBundleDir = '/home/w4live/system-api-update-bundle/' . basename($storeDir);
        $remoteCommand = sprintf(
            'bash -lc %s',
            $this->quoteForBash(sprintf(
                'cd %s && %s && source ./executor.env && %s',
                $this->quoteForBashDouble($remoteBundleDir),
                'chmod +x "./run-update-offline.sh" "./run-update-with-repo-env.sh" "./run-health-checks.sh" "./reconcile-after-reboot.sh"',
                './run-health-checks.sh'
            ))
        );

        $fixtureMap = $this->createVmInfoFixtureMap($vmName, 'running') + [
            $this->fixtureKey([
                $phpBinary,
                $uploadTreeScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--local-dir',
                $storeDir,
                '--remote-dir',
                $remoteBundleDir,
                '--replace',
            ]) => 'uploaded',
            $this->fixtureKey([
                $phpBinary,
                $runRemoteScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--command-b64',
                base64_encode($remoteCommand),
                '--pty',
            ]) => [
                'stdout' => '',
                'stderr' => '',
                'exit_code' => 1,
            ],
            $this->fixtureKey([
                $phpBinary,
                $downloadFilesScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--remote-dir',
                $remoteBundleDir,
                '--local-dir',
                $storeDir,
                '--artifact',
                'operation.json',
                '--artifact',
                'health-report.json',
                '--artifact',
                'events.ndjson',
                '--artifact',
                'health-check-results.json',
                '--artifact',
                'offline-application.json',
                '--artifact',
                'snapshot-manifest.json',
                '--artifact',
                'staging-manifest.json',
            ]) => '{"status":"ok","downloaded":["health-check-results.json"],"missing":[]}',
        ];

        putenv('W4_SYSTEM_API_UPDATE_VM_COMMAND_MAP_JSON=' . json_encode($fixtureMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_system_api_update_bundle_via_virtualbox_vm.php'),
            [
                '--vm-name',
                $vmName,
                '--store-dir',
                $storeDir,
                '--ssh-port',
                '2244',
                '--stage',
                'health',
                '--password',
                'fixture-live-password',
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('boot-entry-present', $result['stderr']);
    }

    public function testReconcileStageFailsWhenSyncedArtifactsConflictWithHealthResults(): void
    {
        $storeDir = $this->createBundleStore('virtualbox-reconcile-mismatch');
        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'health-check-results.json', [
            'health_check_result_schema_version' => 1,
            'kind' => 'health-check-results',
            'status' => 'failed',
            'results' => [
                'boot-entry-present' => 'failed',
            ],
        ]);
        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'operation.json', [
            'update_operation_schema_version' => 1,
            'kind' => 'update-operation',
            'operation_id' => 'fixture-op',
            'profile_id' => 'fixture-profile',
            'source_version' => '1.0.0',
            'target_version' => '1.0.1',
            'stage' => 'confirmed',
        ]);
        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'health-report.json', [
            'health_report_schema_version' => 1,
            'kind' => 'health-report',
            'operation_id' => 'fixture-op',
            'stage' => 'confirmed',
            'status' => 'ok',
        ]);

        $vmName = 'W4-OS-Server-DbusModern';
        $phpBinary = PHP_BINARY;
        $uploadTreeScript = $this->scriptPath('scripts/upload_tree_via_paramiko.php');
        $runRemoteScript = $this->scriptPath('scripts/run_remote_command_via_paramiko.php');
        $downloadFilesScript = $this->scriptPath('scripts/download_files_via_paramiko.php');
        $remoteBundleDir = '/home/w4live/system-api-update-bundle/' . basename($storeDir);
        $remoteCommand = sprintf(
            'bash -lc %s',
            $this->quoteForBash(sprintf(
                'cd %s && %s && source ./executor.env && %s',
                $this->quoteForBashDouble($remoteBundleDir),
                'chmod +x "./run-update-offline.sh" "./run-update-with-repo-env.sh" "./run-health-checks.sh" "./reconcile-after-reboot.sh"',
                './reconcile-after-reboot.sh'
            ))
        );

        $fixtureMap = $this->createVmInfoFixtureMap($vmName, 'running') + [
            $this->fixtureKey([
                $phpBinary,
                $uploadTreeScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--local-dir',
                $storeDir,
                '--remote-dir',
                $remoteBundleDir,
                '--replace',
            ]) => 'uploaded',
            $this->fixtureKey([
                $phpBinary,
                $runRemoteScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--command-b64',
                base64_encode($remoteCommand),
                '--pty',
            ]) => 'reconciled',
            $this->fixtureKey([
                $phpBinary,
                $downloadFilesScript,
                '--host',
                '127.0.0.1',
                '--port',
                '2244',
                '--username',
                'w4live',
                '--password',
                'fixture-live-password',
                '--remote-dir',
                $remoteBundleDir,
                '--local-dir',
                $storeDir,
                '--artifact',
                'operation.json',
                '--artifact',
                'health-report.json',
                '--artifact',
                'events.ndjson',
                '--artifact',
                'health-check-results.json',
                '--artifact',
                'offline-application.json',
                '--artifact',
                'snapshot-manifest.json',
                '--artifact',
                'staging-manifest.json',
            ]) => '{"status":"ok","downloaded":["operation.json","health-report.json","health-check-results.json"],"missing":[]}',
        ];

        putenv('W4_SYSTEM_API_UPDATE_VM_COMMAND_MAP_JSON=' . json_encode($fixtureMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_system_api_update_bundle_via_virtualbox_vm.php'),
            [
                '--vm-name',
                $vmName,
                '--store-dir',
                $storeDir,
                '--ssh-port',
                '2244',
                '--stage',
                'reconcile',
                '--password',
                'fixture-live-password',
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('inconsistente', $result['stderr']);
    }

    private function setVmInfoFixture(string $vmName, string $state): void
    {
        putenv('W4_SYSTEM_API_UPDATE_VM_COMMAND_MAP_JSON=' . json_encode(
            $this->createVmInfoFixtureMap($vmName, $state),
            JSON_THROW_ON_ERROR
        ));
    }

    /**
     * @return array<string, string>
     */
    private function createVmInfoFixtureMap(string $vmName, string $state): array
    {
        return [
            $this->fixtureKey([
                'C:\VBox\VBoxManage.exe',
                'showvminfo',
                $vmName,
                '--machinereadable',
            ]) => implode("\n", [
                sprintf('name="%s"', $vmName),
                'UUID="fixture-vm-uuid"',
                sprintf('VMState="%s"', $state),
                'ostype="Debian_64"',
                'CfgFile="C:\\VirtualBox\\' . $vmName . '.vbox"',
            ]),
        ];
    }

    private function createBundleStore(string $directoryName): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $directoryName . '-' . uniqid('', true);
        mkdir($path, 0777, true);
        $this->tempPaths[] = $path;

        foreach ([
            'executor.env' => "#!/usr/bin/env bash\nexport W4_UPDATE_STORE_DIR=\"\${W4_UPDATE_STORE_DIR:-\${SCRIPT_DIR}}\"\n",
            'run-update-offline.sh' => "#!/usr/bin/env bash\n",
            'run-update-with-repo-env.sh' => "#!/usr/bin/env bash\n",
            'run-health-checks.sh' => "#!/usr/bin/env bash\n",
            'reconcile-after-reboot.sh' => "#!/usr/bin/env bash\n",
            'reconcile-update-operation.php' => "<?php\n",
            'update-executor.json' => "{}\n",
            'update-plan.json' => "{}\n",
            'operation.json' => "{\n  \"update_operation_schema_version\": 1,\n  \"kind\": \"update-operation\",\n  \"operation_id\": \"fixture-op\",\n  \"profile_id\": \"fixture-profile\",\n  \"source_version\": \"1.0.0\",\n  \"target_version\": \"1.0.1\",\n  \"stage\": \"pending_health\"\n}\n",
            'health-report.json' => "{\n  \"health_report_schema_version\": 1,\n  \"kind\": \"health-report\",\n  \"operation_id\": \"fixture-op\",\n  \"stage\": \"pending_health\",\n  \"status\": \"pending\"\n}\n",
            'health-check-results.json' => "{\n  \"health_check_result_schema_version\": 1,\n  \"kind\": \"health-check-results\",\n  \"status\": \"ok\",\n  \"results\": {\n    \"boot-entry-present\": \"ok\"\n  }\n}\n",
        ] as $file => $contents) {
            file_put_contents($path . DIRECTORY_SEPARATOR . $file, $contents);
        }

        return $path;
    }

    /**
     * @param list<string> $command
     */
    private function fixtureKey(array $command): string
    {
        return json_encode($command, JSON_THROW_ON_ERROR);
    }

    private function scriptPath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * @param list<string> $arguments
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runPhpScript(string $scriptPath, array $arguments): array
    {
        $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->rootDir);
        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout === false ? '' : $stdout,
            'stderr' => $stderr === false ? '' : $stderr,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJsonFile(string $path, array $payload): void
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        self::assertNotFalse(file_put_contents($path, $encoded . PHP_EOL));
    }

    private function quoteForBash(string $value): string
    {
        return "'" . str_replace("'", "'\"'\"'", $value) . "'";
    }

    private function quoteForBashDouble(string $value): string
    {
        return '"' . str_replace(
            ['\\', '"', '$', '`'],
            ['\\\\', '\\"', '\\$', '\\`'],
            $value
        ) . '"';
    }


    private function removePath(string $path): void
    {
        if (is_dir($path)) {
            $items = scandir($path);
            if ($items === false) {
                return;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $this->removePath($path . DIRECTORY_SEPARATOR . $item);
            }

            @rmdir($path);
            return;
        }

        @unlink($path);
    }
}
