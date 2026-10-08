<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class ControlCenterHomeCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-control-center-home-cli-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testReadControlCenterHomeScriptPrintsModelAndWritesOutput(): void
    {
        $this->seedFixtures();
        $outputPath = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'control-center-home.json';

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'read_control_center_home.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
                '--output',
                $outputPath,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('control-center-home-model', $payload['kind']);
        self::assertSame('w4-os-home', $payload['profile_id']);
        self::assertSame(4, $payload['summary']['modules_total']);
        self::assertFileExists($outputPath);

        $savedPayload = $this->decodeJsonFile($outputPath);
        self::assertSame($payload['hero']['summary'], $savedPayload['hero']['summary']);
        self::assertSame('confirmed', $this->indexModules($savedPayload['modules'])['updates']['highlights']['stage']);
    }

    public function testReadControlCenterHomeScriptSupportsTextFormat(): void
    {
        $this->seedFixtures();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'read_control_center_home.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
                '--format',
                'text',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Settings · w4-os-home', $result['stdout']);
        self::assertStringContainsString('- Sistema [HEALTHY] (W4-augmented)', $result['stdout']);
        self::assertStringContainsString('Motivo:', $result['stdout']);
    }

    public function testReadControlCenterHomeScriptSupportsModuleDrillDownInJsonAndText(): void
    {
        $this->seedFixtures();

        $jsonResult = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'read_control_center_home.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
                '--module',
                'updates',
            ]
        );

        self::assertSame(0, $jsonResult['exitCode'], $jsonResult['stderr']);
        $jsonPayload = $this->decodeJson($jsonResult['stdout']);
        self::assertSame('control-center-module-detail', $jsonPayload['kind']);
        self::assertSame('updates', $jsonPayload['module']['id']);
        self::assertSame('confirmed', $jsonPayload['module']['highlights']['stage']);

        $textResult = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'read_control_center_home.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
                '--module',
                'updates',
                '--format',
                'text',
            ]
        );

        self::assertSame(0, $textResult['exitCode'], $textResult['stderr']);
        self::assertStringContainsString('Settings · w4-os-home · Actualizaciones', $textResult['stdout']);
        self::assertStringContainsString('Entrypoints:', $textResult['stdout']);
        self::assertStringContainsString('gnome-software --mode=updates', $textResult['stdout']);
    }

    private function seedFixtures(): void
    {
        $this->writeFile(
            'config/editions/home/policy.json',
            <<<'JSON'
{
  "schema_version": 1,
  "profile_id": "w4-os-home",
  "branding": {
    "edition": "Home",
    "hostname_prefix": "w4-home"
  },
  "boot": {
    "default_target": "graphical.target"
  },
  "ssh": {
    "enabled": false
  },
  "firewall": {
    "backend": "ufw",
    "incoming": "deny",
    "outgoing": "allow"
  },
  "storage": {
    "root_filesystem": "btrfs",
    "encryption_default": "luks2"
  },
  "update": {
    "snapshot_backend": "btrfs"
  }
}
JSON
        );

        $this->writeFile(
            'build/install/w4-os-home/installation-profile.derived.json',
            <<<'JSON'
{
  "installation_schema_version": 1,
  "kind": "installation-profile",
  "edition": "home",
  "storage": {
    "layout": "btrfs-luks2",
    "root": {
      "luks_name": "cryptroot",
      "subvolumes": [
        { "name": "@", "mountpoint": "/" },
        { "name": "@home", "mountpoint": "/home" }
      ]
    }
  },
  "identity": {
    "locale": "es_DO.UTF-8",
    "keyboard": "latam",
    "hostname": "w4-home-vm"
  }
}
JSON
        );

        $this->writeFile(
            'build/live-output/w4-os-home/metadata/live-summary.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-home"
W4_LIVE_HOSTNAME="w4-home-live"
W4_GENERATED_AT="2026-10-07T09:39:20Z"
ENV
        );

        $this->writeFile(
            'build/live-output/w4-os-home/image-root/system-overlay/etc/w4/desktop-defaults.json',
            <<<'JSON'
{
  "schema_version": 1,
  "kind": "desktop-defaults",
  "profile_id": "w4-os-home",
  "desktop": {
    "shell": "gnome-shell",
    "session": "gnome",
    "display_manager": "gdm"
  }
}
JSON
        );

        $this->writeFile(
            'build/security/w4-os-home/security-baseline.json',
            <<<'JSON'
{
  "security_baseline_schema_version": 1,
  "kind": "security-baseline",
  "profile_id": "w4-os-home",
  "summary": {
    "implemented": 10,
    "gap": 0
  },
  "controls": [
    {
      "id": "mac-enforcement",
      "expected": {
        "module": "apparmor"
      }
    }
  ]
}
JSON
        );

        $this->writeFile(
            'build/update/validation/w4-update-smoke-003-signed-prod-home-rerun-002/operation.json',
            <<<'JSON'
{
  "update_operation_schema_version": 1,
  "kind": "update-operation",
  "operation_id": "w4-update-smoke-003",
  "profile_id": "w4-os-home",
  "source_version": "1.0.0-lab",
  "target_version": "1.0.1-lab",
  "stage": "confirmed",
  "repository_snapshot": {
    "id": "w4-main-2026-09-19T120000Z",
    "channel": "testing"
  },
  "snapshot": {
    "snapshot_name": "pre-update-w4-update-smoke-003",
    "retention": "protected-until-confirmed"
  },
  "timestamps": {
    "updated_at": "2026-09-23T20:53:10+00:00"
  }
}
JSON
        );

        $this->writeFile(
            'build/update/validation/w4-update-smoke-003-signed-prod-home-rerun-002/health-report.json',
            <<<'JSON'
{
  "health_report_schema_version": 1,
  "kind": "health-report",
  "operation_id": "w4-update-smoke-003",
  "status": "ok",
  "updated_at": "2026-09-23T20:53:10+00:00"
}
JSON
        );

        $this->writeFile(
            'build/update/validation/w4-update-smoke-003-signed-prod-home-rerun-002/snapshot-manifest.json',
            <<<'JSON'
{
  "snapshot_manifest_schema_version": 1,
  "kind": "snapshot-manifest",
  "operation_id": "w4-update-smoke-003",
  "snapshot_name": "pre-update-w4-update-smoke-003",
  "snapshot_path": "/.snapshots/pre-update-w4-update-smoke-003"
}
JSON
        );

        $this->writeFile(
            'build/update/repository-output/w4-main-2026-09-20T180000Z-signed-prod/publication-manifest.json',
            <<<'JSON'
{
  "publication_manifest_schema_version": 1,
  "kind": "published-update-repository",
  "signing_profile": "prod",
  "published_at": "2026-09-22T04:51:03+00:00",
  "repository_snapshot": {
    "id": "w4-main-2026-09-20T180000Z",
    "channel": "testing"
  }
}
JSON
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

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $this->decodeJson($raw);
    }

    /**
     * @param list<array<string, mixed>> $modules
     * @return array<string, array<string, mixed>>
     */
    private function indexModules(array $modules): array
    {
        $indexed = [];
        foreach ($modules as $module) {
            $indexed[(string) $module['id']] = $module;
        }

        return $indexed;
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $path = $this->workspaceRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true), sprintf('No se pudo crear %s', $directory));
        }

        self::assertNotFalse(file_put_contents($path, $contents), sprintf('No se pudo escribir %s', $relativePath));
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
