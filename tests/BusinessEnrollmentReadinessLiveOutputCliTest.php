<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class BusinessEnrollmentReadinessLiveOutputCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-business-enrollment-readiness-live-output-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testValidateBusinessEnrollmentReadinessLiveOutputReturnsExpectedPayload(): void
    {
        $this->seedWorkspace();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_enrollment_readiness_live_output.php',
            [
                '--profile',
                'w4-os-business',
                '--root-dir',
                $this->workspaceRoot,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ok', $payload['status']);
        self::assertSame('business-enrollment-readiness-live-output-validation', $payload['kind']);
        self::assertSame('w4-os-business', $payload['profile_id']);
        self::assertContains('business-policy-hooks', $payload['feature_flags']);
        self::assertContains('device-enrollment-ready', $payload['feature_flags']);
        self::assertSame('w4-business-live', $payload['baseline_gate']['live_hostname']);
        self::assertSame('image-root/system-overlay/etc/w4/business-enrollment-readiness.json', $payload['runtime_projection']['live_contract']);
        self::assertSame('/var/lib/w4/policy/last-known-policy.json', $payload['policy_contract']['last_known_policy_file']);
        self::assertSame('/var/lib/w4/inventory/device-state.json', $payload['inventory_contract']['device_state_file']);
        self::assertSame('available', $payload['capabilities'][0]['status']);
        self::assertContains('remote-inventory-sync', $payload['deferred_steps']);
    }

    public function testValidateBusinessEnrollmentReadinessLiveOutputSupportsTextFormat(): void
    {
        $this->seedWorkspace();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_enrollment_readiness_live_output.php',
            [
                '--profile',
                'w4-os-business',
                '--root-dir',
                $this->workspaceRoot,
                '--format',
                'text',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Business enrollment readiness live output', $result['stdout']);
        self::assertStringContainsString('Contract: image-root/system-overlay/etc/w4/business-enrollment-readiness.json', $result['stdout']);
        self::assertStringContainsString('Contrato de readiness materializado en live-output: available', $result['stdout']);
        self::assertStringContainsString('Anclaje local de estado de inventario: available', $result['stdout']);
    }

    private function seedWorkspace(): void
    {
        $contract = <<<'JSON'
{
  "schema_version": 1,
  "kind": "business-enrollment-readiness",
  "profile_id": "w4-os-business",
  "scope": {
    "mode": "local-readiness",
    "backend_required": false,
    "initial_state": "unenrolled-ready"
  },
  "device_identity": {
    "state_file": "/var/lib/w4/device-identity/identity.json",
    "private_key_file": "/var/lib/w4/device-identity/device.key",
    "credential_file": "/var/lib/w4/device-identity/device.crt",
    "hostname_prefix": "w4-business"
  },
  "policy": {
    "last_known_policy_file": "/var/lib/w4/policy/last-known-policy.json",
    "effective_policy_file": "/var/lib/w4/policy/effective-policy.json",
    "invalid_policy_behavior": "keep-last-valid"
  },
  "inventory": {
    "device_state_file": "/var/lib/w4/inventory/device-state.json"
  },
  "deferred_steps": [
    "token-issuer-backend",
    "device-keypair-materialization",
    "credential-issuance",
    "first-policy-download",
    "remote-inventory-sync"
  ]
}
JSON;

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'enrollment-readiness.json',
            $contract
        );

        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . 'w4-os-business';

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-business"
W4_PROFILE_NAME="W4 OS Business"
W4_LIVE_USER="w4live"
W4_LIVE_HOSTNAME="w4-business-live"
W4_DEFAULT_TARGET="graphical.target"
W4_GENERATED_AT="2026-10-10T12:30:00Z"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-business"
W4_PROFILE_NAME="W4 OS Business"
W4_HOSTNAME="w4-business"
W4_LIVE_HOSTNAME="w4-business-live"
W4_LIVE_USER="w4live"
W4_DEFAULT_TARGET="graphical.target"
W4_FEATURES="desktop-defaults,business-policy-hooks,device-enrollment-ready,inventory-ready,kde-sddm-default-route,reversible-branding-defaults"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json',
            <<<'JSON'
{
  "schema_version": 1,
  "kind": "desktop-defaults",
  "profile_id": "w4-os-business",
  "desktop": {
    "shell": "plasma-desktop",
    "session": "plasma",
    "display_manager": "sddm"
  },
  "application": {
    "method": "overlay-kde-defaults",
    "scope": "profile-defaults"
  },
  "wallpaper": {
    "uri": "file:///usr/share/w4/branding/business/wallpapers/w4-business-default.svg",
    "asset_path": "/usr/share/w4/branding/business/wallpapers/w4-business-default.svg"
  },
  "favorites": [
    "org.kde.dolphin.desktop",
    "systemsettings.desktop",
    "firefox-esr.desktop",
    "org.libreoffice.LibreOffice.StartCenter.desktop",
    "org.kde.konsole.desktop"
  ]
}
JSON
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-enrollment-readiness.json',
            $contract
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'branding' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'wallpapers' . DIRECTORY_SEPARATOR . 'w4-business-default.svg',
            <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="3840" height="2160" viewBox="0 0 3840 2160">
  <text x="0" y="40">W4 OS Business</text>
  <text x="0" y="80">PLASMA + SDDM default route</text>
</svg>
SVG
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . '.keep',
            "materialized\n"
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest',
            <<<'TXT'
dolphin 24.12.0-1
konsole 24.12.0-1
plasma-desktop 6.3.6-1
plasma-nm 6.3.6-1
plasma-workspace 6.3.6-1
sddm 0.21.0-2
systemsettings 6.3.6-1
xdg-desktop-portal-kde 6.3.6-1
TXT
        );
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

        $process = proc_open($command, $descriptorSpec, $pipes, $this->repoRoot);
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

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true), sprintf('No se pudo crear %s', $directory));
        }

        self::assertNotFalse(file_put_contents($path, $contents), sprintf('No se pudo escribir %s', $path));
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
