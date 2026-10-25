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
        self::assertStringEndsWith('scripts\enable_live_ssh_in_virtualbox.php', $payload['enable_live_ssh_script']);
        self::assertStringEndsWith('scripts\upload_tree_via_paramiko.php', $payload['upload_tree_script']);
        self::assertStringEndsWith('scripts\run_remote_command_via_paramiko.php', $payload['run_remote_command_script']);
    }

    public function testExecutionReturnsCanonicalOkPayload(): void
    {
        $storeDir = $this->createBundleStore('virtualbox-execution');
        $vmName = 'W4-OS-Server-DbusModern';
        $phpBinary = PHP_BINARY;
        $enableLiveSshScript = $this->scriptPath('scripts/enable_live_ssh_in_virtualbox.php');
        $uploadTreeScript = $this->scriptPath('scripts/upload_tree_via_paramiko.php');
        $runRemoteScript = $this->scriptPath('scripts/run_remote_command_via_paramiko.php');
        $remoteBundleDir = '/home/w4live/system-api-update-bundle/' . basename($storeDir);
        $remoteCommand = sprintf(
            'bash -lc %s',
            $this->quoteForBash(sprintf(
                'cd %s && %s && source ./executor.env && %s',
                $this->quoteForBash($remoteBundleDir),
                'chmod +x \'./run-update-offline.sh\' \'./run-update-with-repo-env.sh\' \'./run-health-checks.sh\' \'./reconcile-after-reboot.sh\'',
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
                '--command',
                $remoteCommand,
                '--pty',
            ]) => 'health-checks-ok',
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
        self::assertSame('reconcile', $payload['next_step']);

        $liveSshPayload = $this->decodeJson($payload['live_ssh_setup_output']);
        self::assertSame('ok', $liveSshPayload['status']);
        self::assertSame($vmName, $liveSshPayload['vm_name']);
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
            'update-executor.json' => "{}\n",
            'update-plan.json' => "{}\n",
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

    private function quoteForBash(string $value): string
    {
        return "'" . str_replace("'", "'\"'\"'", $value) . "'";
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
