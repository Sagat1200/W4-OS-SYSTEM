<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class ServerVirtualBoxInstallFlowCliTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        putenv('W4_VBOXMANAGE_PATH=C:\VBox\VBoxManage.exe');
    }

    protected function tearDown(): void
    {
        putenv('W4_VBOXMANAGE_PATH');
        putenv('W4_SERVER_VBOX_FLOW_COMMAND_MAP_JSON');
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
}
