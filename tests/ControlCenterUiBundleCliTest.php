<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class ControlCenterUiBundleCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-control-center-ui-bundle-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testGenerateControlCenterUiBundleCreatesStaticPagesFromApiContract(): void
    {
        $this->seedFixtures();
        $this->generateSnapshot();

        $outputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'control-center-ui';
        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_control_center_ui_bundle.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
                '--output-dir',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('w4-os-home', $payload['profile_id']);
        self::assertSame('index.html', $payload['routes']['home']);
        self::assertSame('modules/updates.html', $payload['routes']['modules']['updates']);
        self::assertCount(7, $payload['generated_files']);
        self::assertContains('styles/control-center.css', $payload['generated_files']);

        $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'control-center-ui-manifest.json';
        self::assertFileExists($manifestPath);
        $manifest = $this->decodeJsonFile($manifestPath);
        self::assertSame('control-center-ui-bundle', $manifest['kind']);
        self::assertSame('control-center-api-response', $manifest['source_kind']);
        self::assertSame('index.html', $manifest['routes']['home']);
        self::assertSame('modules/security.html', $manifest['routes']['modules']['security']);

        $indexHtml = $this->readFile($outputDir . DIRECTORY_SEPARATOR . 'index.html');
        self::assertStringContainsString('<title>Settings · w4-os-home</title>', $indexHtml);
        self::assertStringContainsString('modules/updates.html', $indexHtml);
        self::assertStringContainsString('W4 OS · Control Center', $indexHtml);

        $moduleHtml = $this->readFile($outputDir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'updates.html');
        self::assertStringContainsString('Snapshot', $moduleHtml);
        self::assertStringContainsString('gnome-software --mode=updates', $moduleHtml);
        self::assertStringContainsString('../styles/control-center.css', $moduleHtml);
    }

    private function generateSnapshot(): void
    {
        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_control_center_snapshot.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
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
        return $this->decodeJson($this->readFile($path));
    }

    private function readFile(string $path): string
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $raw;
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
