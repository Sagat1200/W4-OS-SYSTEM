<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class VirtualBoxLiveSshCliTest extends TestCase
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
        putenv('W4_VBOX_LIVE_SSH_COMMAND_MAP_JSON');
    }

    public function testCheckOnlyReturnsCanonicalReadyPayload(): void
    {
        $result = $this->runPhpScript(
            $this->scriptPath('scripts/enable_live_ssh_in_virtualbox.php'),
            [
                '--vm-name',
                'W4-VM-Fixture',
                '--live-password',
                'fixture-password',
                '--ssh-port',
                '2234',
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ready', $payload['status']);
        self::assertSame('W4-VM-Fixture', $payload['vm_name']);
        self::assertSame('127.0.0.1', $payload['ssh_host']);
        self::assertSame(2234, $payload['ssh_port']);
        self::assertSame('w4live', $payload['live_user']);
        self::assertSame(5, $payload['setup_step_count']);
        self::assertSame(['e0', '1c', 'e0', '9c'], $payload['enter_scancode']);
        self::assertSame('C:\VBox\VBoxManage.exe', $payload['vboxmanage_path']);
        self::assertArrayNotHasKey('live_password', $payload);
    }

    public function testExecutionReturnsCanonicalOkPayload(): void
    {
        $vmName = 'W4-VM-Execution';
        $vboxManage = 'C:\VBox\VBoxManage.exe';
        $steps = [
            'sudo mkdir -p /run/sshd /etc/ssh/sshd_config.d',
            "printf %s 'w4live:fixture-password' | sudo /usr/sbin/chpasswd",
            "printf 'PasswordAuthentication yes\nKbdInteractiveAuthentication yes\nPubkeyAuthentication yes\nAuthenticationMethods any\nUsePAM yes\nPermitEmptyPasswords no\n' | sudo tee /etc/ssh/sshd_config.d/99-w4-live-password.conf >/dev/null",
            'sudo ufw allow 22/tcp',
            'sudo sshd -t && sudo systemctl restart ssh',
        ];

        $fixtureMap = [];
        foreach ($steps as $step) {
            $fixtureMap[$this->fixtureKey([$vboxManage, 'controlvm', $vmName, 'keyboardputstring', $step])] = '';
            $fixtureMap[$this->fixtureKey([$vboxManage, 'controlvm', $vmName, 'keyboardputscancode', 'e0', '1c', 'e0', '9c'])] = '';
        }

        putenv('W4_VBOX_LIVE_SSH_COMMAND_MAP_JSON=' . json_encode($fixtureMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/enable_live_ssh_in_virtualbox.php'),
            [
                '--vm-name',
                $vmName,
                '--live-password',
                'fixture-password',
                '--ssh-port',
                '2234',
                '--wait-ms',
                '0',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ok', $payload['status']);
        self::assertSame($vmName, $payload['vm_name']);
        self::assertSame('127.0.0.1', $payload['ssh_host']);
        self::assertSame(2234, $payload['ssh_port']);
        self::assertSame('w4live', $payload['live_user']);
        self::assertSame($steps === [] ? [] : [
            'ensure_sshd_runtime_dirs',
            'set_live_password',
            'write_password_auth_override',
            'allow_ssh_in_ufw',
            'restart_ssh_service',
        ], $payload['executed_steps']);
        self::assertSame(5, $payload['setup_step_count']);
        self::assertSame(0, $payload['wait_ms']);
        self::assertSame(['e0', '1c', 'e0', '9c'], $payload['enter_scancode']);
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
}
