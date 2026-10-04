<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class ServerVirtualBoxInstallFlowCliTest extends TestCase
{
    private string $rootDir;
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        putenv('W4_VBOXMANAGE_PATH=C:\VBox\VBoxManage.exe');
    }

    protected function tearDown(): void
    {
        putenv('W4_VBOXMANAGE_PATH');
        putenv('W4_SERVER_VBOX_FLOW_COMMAND_MAP_JSON');

        foreach (array_reverse($this->tempPaths) as $path) {
            $this->removePath($path);
        }

        $this->tempPaths = [];
    }

    public function testCheckOnlyReturnsCanonicalReadyPayload(): void
    {
        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_server_virtualbox_install_flow.php'),
            [
                '--vm-name',
                'W4-OS-Server-Auto-Fixture',
                '--ssh-port',
                '2240',
                '--disk-size-mib',
                '8192',
                '--check-only',
                '--live-password',
                'fixture-password',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ready', $payload['status']);
        self::assertSame('w4-os-server', $payload['profile_id']);
        self::assertSame('W4-OS-Server-Auto-Fixture', $payload['vm_name']);
        self::assertSame(2240, $payload['ssh_port']);
        self::assertSame(8192, $payload['disk_size_mib']);
        self::assertSame('C:\VBox\VBoxManage.exe', $payload['vboxmanage_path']);
        self::assertSame('guestssh,tcp,127.0.0.1,2240,,22', $payload['nat_rule']);
        self::assertSame('pci-0000:00:0d.0-ata-1.0', $payload['expected_disk_by_path']);
        self::assertStringEndsWith('scripts\\enable_live_ssh_in_virtualbox.php', $payload['enable_live_ssh_script']);
        self::assertStringEndsWith('scripts\\run_security_baseline_via_paramiko.php', $payload['run_security_baseline_script']);
        self::assertStringEndsWith('build\\install\\w4-os-server-w4-os-server-auto-fixture', $payload['bundle_dir']);
        self::assertStringEndsWith('build\\install-inventory\\virtualbox-server-w4-os-server-auto-fixture.json', $payload['inventory_path']);
        self::assertStringEndsWith('build\\security\\w4-os-server', $payload['baseline_bundle_dir']);
        self::assertStringEndsWith('build\\security\\validation\\w4-os-server-w4-os-server-auto-fixture', $payload['baseline_evidence_dir']);
        self::assertSame('/home/w4admin/w4-security-baseline-w4-os-server-auto-fixture', $payload['baseline_remote_root']);
        self::assertStringEndsWith('security-baseline-report.json', $payload['baseline_report_path']);
        self::assertStringEndsWith('server-auto-fixture-before-luks.png', $payload['before_luks_screenshot_path']);
        self::assertStringEndsWith('server-auto-fixture-postlogin.png', $payload['postlogin_screenshot_path']);
        self::assertStringEndsWith('server-auto-fixture-install-validation.txt', $payload['validation_summary_path']);
        self::assertTrue($payload['runtime_validation_enabled']);
        self::assertSame('w4admin', $payload['runtime_username']);
        self::assertSame(300, $payload['connect_wait']);
        self::assertSame(5, $payload['retry_interval']);
        self::assertSame(20, $payload['unlock_wait']);
        self::assertSame(10, $payload['unlock_retry_interval']);
        self::assertSame(12, $payload['unlock_retries']);
        self::assertSame(180, $payload['unlock_window']);
        self::assertArrayNotHasKey('live_password', $payload);
    }

    public function testExecutionReturnsCanonicalOkPayloadAndWritesValidationSummary(): void
    {
        $vmName = 'W4-OS-Server-Auto-Fixture';
        $baseDir = $this->createTempDirectory('server-flow-base-');
        $secretsDir = $this->createTempDirectory('server-flow-secrets-');
        $baselineEvidenceDir = $this->createTempDirectory('server-flow-baseline-');
        $baselineBundleDir = $this->createTempDirectory('server-flow-bundle-');
        $inventoryPath = $baseDir . DIRECTORY_SEPARATOR . 'inventory.json';
        $bundleDir = $baseDir . DIRECTORY_SEPARATOR . 'bundle';
        $isoPath = $baseDir . DIRECTORY_SEPARATOR . 'w4-os-server-live-amd64.iso';
        $localUserPassword = 'fixture-local-password';
        $diskPassphrase = 'fixture-disk-passphrase';
        $livePassword = 'fixture-live-password';
        $vboxManage = 'C:\VBox\VBoxManage.exe';
        $vmDir = $baseDir . DIRECTORY_SEPARATOR . $vmName;
        $diskPath = $vmDir . DIRECTORY_SEPARATOR . $vmName . '.vdi';
        $runtimeOutput = "w4-server-vm\nactive\nactive\n";
        $baselinePayload = [
            'status' => 'ok',
            'username' => 'w4admin',
            'sudo' => true,
            'vm_name' => $vmName,
            'luks_unlock_enabled' => true,
            'report_summary' => [
                'passed' => 9,
                'failed' => 0,
                'skipped' => 1,
            ],
            'remote_exit_code' => 0,
        ];
        $baselineMixedOutput = json_encode([
            'security_baseline_report_schema_version' => 1,
            'kind' => 'security-baseline-report',
            'summary' => [
                'passed' => 9,
                'failed' => 0,
                'skipped' => 1,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            . PHP_EOL
            . json_encode($baselinePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        file_put_contents($isoPath, 'fixture-iso');
        file_put_contents($secretsDir . DIRECTORY_SEPARATOR . 'local-user-password.txt', $localUserPassword . PHP_EOL);
        file_put_contents($secretsDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt', $diskPassphrase . PHP_EOL);

        $enableLiveSshScript = $this->scriptPath('scripts/enable_live_ssh_in_virtualbox.php');
        $prepareBundleScript = $this->scriptPath('scripts/prepare_installation_bundle.php');
        $uploadTreeScript = $this->scriptPath('scripts/upload_tree_via_paramiko.php');
        $runRemoteScript = $this->scriptPath('scripts/run_remote_command_via_paramiko.php');
        $runSecurityBaselineScript = $this->scriptPath('scripts/run_security_baseline_via_paramiko.php');

        $fixtureMap = [
            $this->fixtureKey([PHP_BINARY, $prepareBundleScript, '--profile', 'w4-os-server', '--disk-inventory', $inventoryPath, '--bundle-dir', $bundleDir]) => '',
            $this->fixtureKey([$vboxManage, 'createvm', '--name', $vmName, '--ostype', 'Debian_64', '--basefolder', $baseDir, '--register']) => '',
            $this->fixtureKey([$vboxManage, 'modifyvm', $vmName, '--memory', '2048', '--cpus', '2', '--firmware', 'efi', '--graphicscontroller', 'vmsvga', '--vram', '32', '--audio-enabled', 'off', '--boot1', 'dvd', '--boot2', 'disk', '--boot3', 'none', '--boot4', 'none', '--nic1', 'nat']) => '',
            $this->fixtureKey([$vboxManage, 'modifyvm', $vmName, '--natpf1', 'guestssh,tcp,127.0.0.1,2240,,22']) => '',
            $this->fixtureKey([$vboxManage, 'storagectl', $vmName, '--name', 'SATA', '--add', 'sata', '--controller', 'IntelAhci']) => '',
            $this->fixtureKey([$vboxManage, 'createmedium', 'disk', '--filename', $diskPath, '--size', '32768', '--format', 'VDI']) => '',
            $this->fixtureKey([$vboxManage, 'storageattach', $vmName, '--storagectl', 'SATA', '--port', '0', '--device', '0', '--type', 'hdd', '--medium', $diskPath]) => '',
            $this->fixtureKey([$vboxManage, 'storageattach', $vmName, '--storagectl', 'SATA', '--port', '1', '--device', '0', '--type', 'dvddrive', '--medium', $isoPath]) => '',
            $this->fixtureKey([$vboxManage, 'startvm', $vmName, '--type', 'headless']) => '',
            $this->fixtureKey([PHP_BINARY, $enableLiveSshScript, '--vm-name', $vmName, '--live-password', $livePassword, '--ssh-host', '127.0.0.1', '--ssh-port', '2240', '--wait-ms', '0']) => json_encode([
                'status' => 'ok',
                'vm_name' => $vmName,
                'ssh_host' => '127.0.0.1',
                'ssh_port' => 2240,
                'live_user' => 'w4live',
                'executed_steps' => [
                    'ensure_sshd_runtime_dirs',
                    'set_live_password',
                    'write_password_auth_override',
                    'allow_ssh_in_ufw',
                    'restart_ssh_service',
                ],
                'setup_step_count' => 5,
                'wait_ms' => 0,
                'enter_scancode' => ['e0', '1c', 'e0', '9c'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $this->fixtureKey([PHP_BINARY, $uploadTreeScript, '--host', '127.0.0.1', '--port', '2240', '--username', 'w4live', '--password', $livePassword, '--local-dir', $bundleDir, '--remote-dir', '/home/w4live/w4-install-server', '--replace']) => '',
            $this->fixtureKey([PHP_BINARY, $uploadTreeScript, '--host', '127.0.0.1', '--port', '2240', '--username', 'w4live', '--password', $livePassword, '--local-dir', $secretsDir, '--remote-dir', '/home/w4live/w4-install-secrets', '--replace']) => '',
            $this->fixtureKey([PHP_BINARY, $runRemoteScript, '--host', '127.0.0.1', '--port', '2240', '--username', 'w4live', '--password', $livePassword, '--command', "sudo bash -lc 'cd /home/w4live/w4-install-server && rm -f install.log install.exit && W4_INSTALL_EXECUTE=1 W4_INSTALL_SOURCE_ROOTFS=/run/live/rootfs/filesystem.squashfs W4_INSTALL_SOURCE_SQUASHFS=/run/live/medium/live/filesystem.squashfs W4_DISK_PASSPHRASE_FILE=/home/w4live/w4-install-secrets/disk-passphrase.txt W4_LOCAL_USER_PASSWORD_FILE=/home/w4live/w4-install-secrets/local-user-password.txt bash ./apply-installation.sh > install.log 2>&1; code=$?; echo \"\$code\" > install.exit; echo __W4_INSTALL_EXIT__:\$code'", '--pty']) => "__W4_INSTALL_EXIT__:0\n",
            $this->fixtureKey([$vboxManage, 'controlvm', $vmName, 'acpipowerbutton']) => '',
            $this->fixtureKey([$vboxManage, 'showvminfo', $vmName, '--machinereadable']) => 'VMState="poweroff"',
            $this->fixtureKey([$vboxManage, 'storageattach', $vmName, '--storagectl', 'SATA', '--port', '1', '--device', '0', '--medium', 'none']) => '',
            $this->fixtureKey([$vboxManage, 'modifyvm', $vmName, '--boot1', 'disk', '--boot2', 'none', '--boot3', 'none', '--boot4', 'none']) => '',
            $this->fixtureKey([$vboxManage, 'controlvm', $vmName, 'screenshotpng', $baseDir . DIRECTORY_SEPARATOR . 'server-auto-fixture-before-luks.png']) => '',
            $this->fixtureKey([PHP_BINARY, $runSecurityBaselineScript, '--host', '127.0.0.1', '--port', '2240', '--username', 'w4admin', '--password', $localUserPassword, '--bundle-dir', $baselineBundleDir, '--remote-root', '/home/w4admin/w4-security-baseline-w4-os-server-auto-fixture', '--evidence-dir', $baselineEvidenceDir, '--sudo', '--vm-name', $vmName, '--luks-passphrase', $diskPassphrase, '--unlock-wait', '20', '--unlock-retry-interval', '10', '--unlock-retries', '12', '--unlock-window', '180', '--connect-wait', '300', '--retry-interval', '5']) => $baselineMixedOutput,
            $this->fixtureKey([$vboxManage, 'controlvm', $vmName, 'screenshotpng', $baseDir . DIRECTORY_SEPARATOR . 'server-auto-fixture-postlogin.png']) => '',
            $this->fixtureKey([PHP_BINARY, $runRemoteScript, '--host', '127.0.0.1', '--port', '2240', '--username', 'w4admin', '--password', $localUserPassword, '--command', 'hostname && systemctl is-active ssh && systemctl is-active w4-firstboot']) => $runtimeOutput,
        ];

        putenv('W4_SERVER_VBOX_FLOW_COMMAND_MAP_JSON=' . json_encode($fixtureMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_server_virtualbox_install_flow.php'),
            [
                '--vm-name',
                $vmName,
                '--ssh-port',
                '2240',
                '--live-password',
                $livePassword,
                '--boot-wait',
                '0',
                '--wait-ms',
                '0',
                '--base-dir',
                $baseDir,
                '--iso-path',
                $isoPath,
                '--secrets-dir',
                $secretsDir,
                '--bundle-dir',
                $bundleDir,
                '--inventory-path',
                $inventoryPath,
                '--baseline-bundle-dir',
                $baselineBundleDir,
                '--baseline-evidence-dir',
                $baselineEvidenceDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ok', $payload['status']);
        self::assertSame('w4-os-server', $payload['profile_id']);
        self::assertSame($vmName, $payload['vm_name']);
        self::assertSame('0', $payload['installation_exit']);
        self::assertSame('complete', $payload['next_step']);
        self::assertSame('w4-server-vm', $payload['runtime_state']['hostname']);
        self::assertSame('active', $payload['runtime_state']['ssh_status']);
        self::assertSame('active', $payload['runtime_state']['firstboot_status']);
        self::assertSame('ok', $payload['baseline_validation_output']['status']);
        self::assertSame(9, $payload['baseline_validation_output']['report_summary']['passed']);
        self::assertSame(0, $payload['baseline_validation_output']['report_summary']['failed']);
        self::assertSame(1, $payload['baseline_validation_output']['report_summary']['skipped']);

        $liveSshPayload = $this->decodeJson($payload['live_ssh_setup_output']);
        self::assertSame('ok', $liveSshPayload['status']);
        self::assertSame([
            'ensure_sshd_runtime_dirs',
            'set_live_password',
            'write_password_auth_override',
            'allow_ssh_in_ufw',
            'restart_ssh_service',
        ], $liveSshPayload['executed_steps']);

        self::assertFileExists($inventoryPath);
        $inventory = json_decode((string) file_get_contents($inventoryPath), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pci-0000:00:0d.0-ata-1.0', $inventory['disks'][0]['by_path']);

        self::assertFileExists($payload['validation_summary_path']);
        $validationSummary = (string) file_get_contents($payload['validation_summary_path']);
        self::assertStringContainsString('RUNNER_STATUS=ok', $validationSummary);
        self::assertStringContainsString('BASELINE_SUMMARY=9 passed / 0 failed / 1 skipped', $validationSummary);
        self::assertStringContainsString('POST_BOOT_HOSTNAME=w4-server-vm', $validationSummary);
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

    private function createTempDirectory(string $prefix): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . uniqid('', true);
        mkdir($path, 0777, true);
        $this->tempPaths[] = $path;

        return $path;
    }

    private function removePath(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->removePath($path . DIRECTORY_SEPARATOR . $entry);
        }

        @rmdir($path);
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
}
