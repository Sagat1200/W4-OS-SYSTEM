<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\ControlCenter\ControlCenterSliceAToolkit;

final class ControlCenterSliceAToolkitTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-control-center-tests-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->rootDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootDir);
    }

    public function testCreateReadModelForHomeSliceAReturnsFourModulesWithEvidence(): void
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
  "id": "w4-os-home-vm-empty-disk-dev-sda",
  "name": "W4 OS Home VM Empty Disk (/dev/sda)",
  "edition": "home",
  "build_profile_id": "w4-os-home",
  "target": {
    "disk_selector": {
      "serial": "fixture-disk"
    },
    "expected_state": "empty",
    "confirm_destroy": true
  },
  "storage": {
    "table": "gpt",
    "layout": "btrfs-luks2",
    "esp": {
      "size_mib": 512
    },
    "boot": {
      "size_mib": 2048
    },
    "root": {
      "luks_name": "cryptroot",
      "subvolumes": [
        {
          "name": "@",
          "mountpoint": "/"
        },
        {
          "name": "@home",
          "mountpoint": "/home"
        },
        {
          "name": "@log",
          "mountpoint": "/var/log"
        },
        {
          "name": "@cache",
          "mountpoint": "/var/cache"
        },
        {
          "name": "@data",
          "mountpoint": "/var/lib/w4"
        }
      ]
    }
  },
  "identity": {
    "locale": "es_DO.UTF-8",
    "keyboard": "latam",
    "hostname": "w4-home-vm",
    "account": {
      "username": "w4",
      "display_name": "W4 User",
      "password_source": "secret://install/local-user-password"
    }
  },
  "security": {
    "encryption": {
      "enabled": true,
      "type": "luks2-passphrase",
      "passphrase_source": "secret://install/disk-passphrase"
    }
  },
  "installer": {
    "engine": "debian-compatible-mvp",
    "revalidate_before_write": true
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

        $toolkit = new ControlCenterSliceAToolkit($this->rootDir);
        $model = $toolkit->createReadModel('w4-os-home');

        self::assertSame('control-center-slice-a-read-model', $model['kind']);
        self::assertSame('w4-os-home', $model['profile_id']);
        self::assertCount(4, $model['modules']);

        $modules = $this->indexModules($model['modules']);

        self::assertSame('W4-augmented', $modules['system']['class']);
        self::assertSame(['read', 'deep-link'], $modules['system']['capabilities']);
        self::assertSame('w4-home', $modules['system']['evidence']['hostname_prefix']);
        self::assertSame('gnome-shell', $modules['system']['evidence']['shell']);
        self::assertSame('w4-home-live', $modules['system']['evidence']['live_hostname']);
        self::assertContains('config/editions/home/policy.json', $modules['system']['source_of_truth']);

        self::assertSame(10, $modules['security']['evidence']['implemented_controls']);
        self::assertSame(0, $modules['security']['evidence']['gap_controls']);
        self::assertSame('ufw', $modules['security']['evidence']['firewall_backend']);
        self::assertFalse($modules['security']['evidence']['ssh_enabled_by_policy']);

        self::assertSame('confirmed', $modules['updates']['evidence']['stage']);
        self::assertSame('1.0.1-lab', $modules['updates']['evidence']['target_version']);
        self::assertSame('prod', $modules['updates']['evidence']['published_signing_profile']);
        self::assertSame('pre-update-w4-update-smoke-003', $modules['updates']['evidence']['snapshot_name']);

        self::assertSame('btrfs', $modules['storage']['evidence']['root_filesystem']);
        self::assertSame('luks2', $modules['storage']['evidence']['encryption_default']);
        self::assertSame(5, $modules['storage']['evidence']['subvolume_count']);
        self::assertSame('pre-update-w4-update-smoke-003', $modules['storage']['evidence']['latest_snapshot_name']);
    }

    public function testCreateReadModelHandlesMissingUpdateArtifactsGracefully(): void
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
  "id": "w4-os-home-vm-empty-disk-dev-sda",
  "name": "W4 OS Home VM Empty Disk (/dev/sda)",
  "edition": "home",
  "build_profile_id": "w4-os-home",
  "target": {
    "disk_selector": {
      "serial": "fixture-disk"
    },
    "expected_state": "empty",
    "confirm_destroy": true
  },
  "storage": {
    "table": "gpt",
    "layout": "btrfs-luks2",
    "esp": {
      "size_mib": 512
    },
    "boot": {
      "size_mib": 2048
    },
    "root": {
      "luks_name": "cryptroot",
      "subvolumes": [
        {
          "name": "@",
          "mountpoint": "/"
        }
      ]
    }
  },
  "identity": {
    "locale": "es_DO.UTF-8",
    "keyboard": "latam",
    "hostname": "w4-home-vm",
    "account": {
      "username": "w4",
      "display_name": "W4 User",
      "password_source": "secret://install/local-user-password"
    }
  },
  "security": {
    "encryption": {
      "enabled": true,
      "type": "luks2-passphrase",
      "passphrase_source": "secret://install/disk-passphrase"
    }
  },
  "installer": {
    "engine": "debian-compatible-mvp",
    "revalidate_before_write": true
  }
}
JSON
        );

        $toolkit = new ControlCenterSliceAToolkit($this->rootDir);
        $model = $toolkit->createReadModel('w4-os-home');
        $modules = $this->indexModules($model['modules']);

        self::assertSame('', $modules['updates']['evidence']['operation_id']);
        self::assertSame('', $modules['updates']['evidence']['stage']);
        self::assertSame([], $modules['updates']['actions']);
        self::assertStringContainsString('No hay evidencia materializada', $modules['updates']['summary']);
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
