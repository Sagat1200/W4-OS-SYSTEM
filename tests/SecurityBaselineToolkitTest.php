<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Security\SecurityBaselineToolkit;

final class SecurityBaselineToolkitTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-security-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testCreateBaselineForHomeTracksImplementedControlsAndGaps(): void
    {
        $toolkit = new SecurityBaselineToolkit($this->rootDir);

        $baseline = $toolkit->createBaseline(
            'w4-os-home',
            $this->fixturePath('installer-profiles/w4-os-home.vm-install.json')
        );

        self::assertSame('security-baseline', $baseline['kind']);
        self::assertSame('w4-os-home', $baseline['profile_id']);
        self::assertSame('installed-image', $baseline['scope']);
        self::assertSame(10, $baseline['summary']['implemented']);
        self::assertSame(0, $baseline['summary']['gap']);

        $controls = $this->indexControls($baseline['controls']);
        self::assertSame('implemented', $controls['encrypted-root']['implementation_state']);
        self::assertSame('runtime', $controls['encrypted-root']['validation_scope']);
        self::assertSame('implemented', $controls['firewall-control-plane']['implementation_state']);
        self::assertSame('implemented', $controls['firewall-default-deny-incoming']['implementation_state']);
        self::assertSame('DROP', $controls['firewall-default-deny-incoming']['expected']['input_policy_value']);
        self::assertSame('implemented', $controls['mac-enforcement']['implementation_state']);
        self::assertSame('implemented', $controls['apparmor-enforced-profiles']['implementation_state']);
        self::assertSame(1, $controls['apparmor-enforced-profiles']['expected']['minimum_enforced_profiles']);
        self::assertSame('implemented', $controls['critical-filesystem-permissions']['implementation_state']);
        self::assertSame('runtime', $controls['critical-filesystem-permissions']['validation_scope']);
        self::assertSame('/etc/default/ufw', $controls['critical-filesystem-permissions']['expected']['paths'][5]['path']);
        self::assertSame('/etc/ufw/ufw.conf', $controls['critical-filesystem-permissions']['expected']['paths'][6]['path']);
        self::assertSame('implemented', $controls['remote-admin-disabled-by-default']['implementation_state']);
        self::assertFalse($controls['remote-admin-disabled-by-default']['expected']['policy_enabled']);
        self::assertContains('config/editions/home/policy.json', $controls['remote-admin-disabled-by-default']['evidence']);
        self::assertSame('implemented', $controls['authenticated-updates']['implementation_state']);
        self::assertSame('pipeline', $controls['authenticated-updates']['validation_scope']);
        self::assertSame('w4', $controls['standard-account']['expected']['username']);
    }

    public function testGenerateSecurityBaselineBundleWritesArtifacts(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-business-baseline';

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_security_baseline_bundle.php'),
            [
                '--profile',
                'w4-os-business',
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundleDir, $payload['bundle_dir']);
        self::assertContains('security-baseline.json', $payload['generated_artifacts']);
        self::assertContains('verify-security-baseline.php', $payload['generated_artifacts']);

        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'security-baseline.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'verify-security-baseline.php');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'SECURITY_BASELINE_README.txt');

        $baseline = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'security-baseline.json');
        self::assertSame('w4-os-business', $baseline['profile_id']);
        self::assertSame(10, $baseline['summary']['implemented']);
        self::assertSame(0, $baseline['summary']['gap']);
        $controls = $this->indexControls($baseline['controls']);
        self::assertContains('config/editions/business/policy.json', $controls['remote-admin-disabled-by-default']['evidence']);

        $verifier = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'verify-security-baseline.php');
        self::assertNotFalse($verifier);
        self::assertStringContainsString("addResult(\$results, 'authenticated-updates', 'skipped'", $verifier);
        self::assertStringContainsString("addResult(\$results, 'firewall-control-plane'", $verifier);
        self::assertStringContainsString("addResult(\$results, 'firewall-default-deny-incoming'", $verifier);
        self::assertStringContainsString('function readConfigValue(string $path, string $key): ?string', $verifier);
        self::assertStringContainsString("DEFAULT_INPUT_POLICY", $verifier);
        self::assertStringContainsString("addResult(\$results, 'apparmor-enforced-profiles'", $verifier);
        self::assertStringContainsString('function countEnforcedAppArmorProfiles(string $profilesPath): int', $verifier);
        self::assertStringContainsString("addResult(\$results, 'critical-filesystem-permissions'", $verifier);
        self::assertStringContainsString('function findPermissionViolations(array $expectedPaths): array', $verifier);
        self::assertStringContainsString("'/etc/default/ufw'", $verifier);
        self::assertStringContainsString("'/etc/ufw/ufw.conf'", $verifier);
        self::assertStringContainsString("command -v sudo", $verifier);
        self::assertStringContainsString('function resolveBinary(array $candidates): string', $verifier);
        self::assertStringContainsString("'/usr/sbin'", $verifier);

        $readme = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'SECURITY_BASELINE_README.txt');
        self::assertNotFalse($readme);
        self::assertStringContainsString('php ./verify-security-baseline.php', $readme);
    }

    public function testCreateBaselineForServerAllowsSshWhenEditionPolicyEnablesIt(): void
    {
        $toolkit = new SecurityBaselineToolkit($this->rootDir);

        $baseline = $toolkit->createBaseline(
            'w4-os-server',
            $this->fixturePath('installer-profiles/w4-os-server.vm-install.json')
        );

        self::assertSame('w4-os-server', $baseline['profile_id']);
        self::assertSame(10, $baseline['summary']['implemented']);
        self::assertSame(0, $baseline['summary']['gap']);

        $controls = $this->indexControls($baseline['controls']);
        self::assertArrayHasKey('remote-admin-server-policy', $controls);
        self::assertArrayNotHasKey('remote-admin-disabled-by-default', $controls);
        self::assertSame('implemented', $controls['remote-admin-server-policy']['implementation_state']);
        self::assertTrue($controls['remote-admin-server-policy']['expected']['policy_enabled']);
        self::assertSame(['enabled', 'enabled-runtime'], $controls['remote-admin-server-policy']['expected']['enabled_states_allowed']);
        self::assertFalse($controls['remote-admin-server-policy']['expected']['root_login']);
        self::assertSame('publickey', $controls['remote-admin-server-policy']['expected']['authentication']);
        self::assertContains('config/editions/server/policy.json', $controls['remote-admin-server-policy']['evidence']);
    }

    public function testRunSecurityBaselineViaParamikoSupportsDryRun(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-baseline';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'evidence';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_security_baseline_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--bundle-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/run_security_baseline_via_paramiko.php'),
            [
                '--host',
                '127.0.0.1',
                '--port',
                '2222',
                '--username',
                'w4',
                '--password',
                'W4login1234',
                '--bundle-dir',
                $bundleDir,
                '--remote-root',
                '/home/w4/w4-security-baseline',
                '--evidence-dir',
                $evidenceDir,
                '--dry-run',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('dry-run', $payload['status']);
        self::assertSame('/home/w4/w4-security-baseline', $payload['remote_root']);
        self::assertSame('/home/w4/w4-security-baseline/security-baseline-report.json', $payload['remote_report_path']);
        self::assertStringContainsString('php ./verify-security-baseline.php /home/w4/w4-security-baseline/security-baseline-report.json', $payload['remote_command']);
        self::assertSame($evidenceDir . DIRECTORY_SEPARATOR . 'security-baseline-report.json', $payload['local_report_path']);
        self::assertSame(300, $payload['connect_wait']);
        self::assertSame(5, $payload['retry_interval']);
    }

    public function testRunSecurityBaselineViaParamikoDryRunIncludesLuksOptions(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-baseline-luks';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'evidence-luks';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_security_baseline_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--bundle-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/run_security_baseline_via_paramiko.php'),
            [
                '--host',
                '127.0.0.1',
                '--port',
                '2222',
                '--username',
                'w4',
                '--password',
                'W4login1234',
                '--bundle-dir',
                $bundleDir,
                '--remote-root',
                '/home/w4/w4-security-baseline',
                '--evidence-dir',
                $evidenceDir,
                '--vm-name',
                'W4-OS-Home-Test',
                '--luks-passphrase',
                'W4boot1234',
                '--dry-run',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('W4-OS-Home-Test', $payload['vm_name']);
        self::assertTrue($payload['luks_unlock_enabled']);
        self::assertSame(20, $payload['unlock_wait']);
        self::assertSame(10, $payload['unlock_retry_interval']);
        self::assertSame(12, $payload['unlock_retries']);
        self::assertSame(180, $payload['unlock_window']);
    }

    public function testRunSecurityBaselineViaParamikoDryRunSupportsSudoVerifier(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-baseline-sudo';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'evidence-sudo';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_security_baseline_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--bundle-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/run_security_baseline_via_paramiko.php'),
            [
                '--host',
                '127.0.0.1',
                '--port',
                '2222',
                '--username',
                'w4',
                '--password',
                'W4login1234',
                '--bundle-dir',
                $bundleDir,
                '--remote-root',
                '/home/w4/w4-security-baseline',
                '--evidence-dir',
                $evidenceDir,
                '--sudo',
                '--dry-run',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertTrue($payload['sudo']);
        self::assertStringContainsString("sudo -S -p '' php ./verify-security-baseline.php", $payload['remote_command']);
        self::assertStringNotContainsString('W4login1234', $payload['remote_command']);
    }

    /**
     * @param list<array<string, mixed>> $controls
     * @return array<string, array<string, mixed>>
     */
    private function indexControls(array $controls): array
    {
        $indexed = [];
        foreach ($controls as $control) {
            $indexed[(string) $control['id']] = $control;
        }

        return $indexed;
    }

    /**
     * @param list<string> $arguments
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(string $scriptPath, array $arguments): array
    {
        $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        self::assertIsResource($process, sprintf('No se pudo ejecutar %s', $scriptPath));

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

    private function fixturePath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
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

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
