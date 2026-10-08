<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\ControlCenter\ControlCenterHomeToolkit;

final class ControlCenterHomeToolkitTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-control-center-home-tests-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->rootDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootDir);
    }

    public function testCreateHomeModelBuildsHealthyCardsAndDeepLinks(): void
    {
        $this->seedHomeFixtures(includeUpdateArtifacts: true);

        $toolkit = new ControlCenterHomeToolkit($this->rootDir);
        $model = $toolkit->createHomeModel('w4-os-home');

        self::assertSame('control-center-home-model', $model['kind']);
        self::assertSame('read-first', $model['mode']);
        self::assertSame(4, $model['summary']['modules_total']);
        self::assertSame(4, $model['summary']['healthy_modules']);
        self::assertSame(0, $model['summary']['attention_modules']);
        self::assertSame(0, $model['summary']['unknown_modules']);
        self::assertSame(6, $model['summary']['deep_links_available']);

        $modules = $this->indexModules($model['modules']);

        self::assertSame('healthy', $modules['system']['status']);
        self::assertSame('gnome-shell', $modules['system']['highlights']['shell']);
        self::assertSame('gdm', $modules['system']['highlights']['display_manager']);
        self::assertCount(3, $modules['system']['entrypoints']);

        self::assertSame('healthy', $modules['security']['status']);
        self::assertContains('Deep-link', $modules['security']['badges']);

        self::assertSame('healthy', $modules['updates']['status']);
        self::assertSame('confirmed', $modules['updates']['highlights']['stage']);
        self::assertSame('gnome-software --mode=updates', $modules['updates']['entrypoints'][0]['command']);

        self::assertSame('healthy', $modules['storage']['status']);
        self::assertSame('btrfs', $modules['storage']['highlights']['root_filesystem']);
        self::assertSame([], $modules['storage']['entrypoints']);
    }

    public function testCreateHomeModelMarksMissingUpdateEvidenceAsUnknown(): void
    {
        $this->seedHomeFixtures(includeUpdateArtifacts: false);

        $toolkit = new ControlCenterHomeToolkit($this->rootDir);
        $model = $toolkit->createHomeModel('w4-os-home');
        $modules = $this->indexModules($model['modules']);

        self::assertSame(3, $model['summary']['healthy_modules']);
        self::assertSame(1, $model['summary']['unknown_modules']);
        self::assertSame('unknown', $modules['updates']['status']);
        self::assertContains('Evidencia parcial', $modules['updates']['badges']);
        self::assertStringContainsString('aun no materializan estado suficiente', $model['hero']['summary']);
    }

    public function testCreateModuleDetailModelReturnsDrillDownForRequestedModule(): void
    {
        $this->seedHomeFixtures(includeUpdateArtifacts: true);

        $toolkit = new ControlCenterHomeToolkit($this->rootDir);
        $detail = $toolkit->createModuleDetailModel('w4-os-home', 'updates');

        self::assertSame('control-center-module-detail', $detail['kind']);
        self::assertSame('updates', $detail['module']['id']);
        self::assertSame('healthy', $detail['module']['status']);
        self::assertStringContainsString('confirmada', $detail['detail']['status_reason']);
        self::assertSame(1, $detail['detail']['entrypoint_count']);
        self::assertSame('gnome-software --mode=updates', $detail['detail']['recommended_entrypoint']['command']);
    }

    private function seedHomeFixtures(bool $includeUpdateArtifacts): void
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
    "default_target": "graphical.target",
    "firmware": "uefi"
  },
  "ssh": {
    "enabled": false,
    "root_login": false,
    "authentication": "disabled"
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
  "build_profile_id": "w4-os-home",
  "edition": "home",
  "storage": {
    "layout": "btrfs-luks2",
    "root": {
      "luks_name": "cryptroot",
      "subvolumes": [
        { "name": "@", "mountpoint": "/" },
        { "name": "@home", "mountpoint": "/home" },
        { "name": "@log", "mountpoint": "/var/log" },
        { "name": "@cache", "mountpoint": "/var/cache" },
        { "name": "@data", "mountpoint": "/var/lib/w4" }
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
W4_DEFAULT_TARGET="graphical.target"
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
  "controls": [
    {
      "id": "mac-enforcement",
      "expected": {
        "module": "apparmor"
      }
    },
    {
      "id": "apparmor-enforced-profiles",
      "expected": {
        "minimum_enforced_profiles": 1
      }
    }
  ],
  "summary": {
    "implemented": 10,
    "gap": 0
  }
}
JSON
        );

        if (!$includeUpdateArtifacts) {
            return;
        }

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
  "stage": "confirmed",
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
        $path = $this->rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true), sprintf('No se pudo crear %s', $directory));
        }

        self::assertNotFalse(file_put_contents($path, $contents), sprintf('No se pudo escribir %s', $relativePath));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
