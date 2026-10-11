<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class BusinessRemoteMinimumInventoryCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-business-remote-minimum-inventory-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testValidateBusinessRemoteMinimumInventoryReturnsExpectedPayload(): void
    {
        $this->seedWorkspace();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_remote_minimum_inventory.php',
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
        self::assertSame('business-remote-minimum-inventory-validation', $payload['kind']);
        self::assertContains('inventory-remote-ready', $payload['feature_flags']);
        self::assertSame('configured-or-enrolled', $payload['scope']['activation']);
        self::assertSame('/var/lib/w4/inventory/remote/pending/device-state.json', $payload['payload_contract']['queue_file']);
        self::assertSame('/etc/w4/inventory/remote-endpoint.json', $payload['remote_contract']['endpoint_config_file']);
        self::assertSame('https-json-manifest', $payload['runtime_exports']['W4_REMOTE_INVENTORY_TRANSPORT']);
        self::assertSame('available', $payload['capabilities'][0]['status']);
        self::assertContains('update-window-cohort-feed', $payload['deferred_steps']);
    }

    public function testValidateBusinessRemoteMinimumInventorySupportsTextFormat(): void
    {
        $this->seedWorkspace();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_remote_minimum_inventory.php',
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
        self::assertStringContainsString('Business remote minimum inventory', $result['stdout']);
        self::assertStringContainsString('Activation: configured-or-enrolled', $result['stdout']);
        self::assertStringContainsString('Contrato remoto minimo de inventario: available', $result['stdout']);
        self::assertStringContainsString('Payload minimo remoto con redaccion explicita: available', $result['stdout']);
    }

    private function seedWorkspace(): void
    {
        $localState = <<<'JSON'
{
  "schema_version": 1,
  "kind": "business-pilot-local-state",
  "profile_id": "w4-os-business",
  "scope": {
    "mode": "local-policy-inventory",
    "backend_required": false,
    "policy_state": "edition-baseline-only",
    "inventory_transport": "local-only"
  },
  "policy": {
    "baseline_source": "edition-policy",
    "baseline_runtime_env": "/etc/w4/edition-policy.env",
    "last_known_policy_file": "/var/lib/w4/policy/last-known-policy.json",
    "effective_policy_file": "/var/lib/w4/policy/effective-policy.json",
    "conflict_report_file": "/var/lib/w4/policy/conflicts.json",
    "invalid_policy_behavior": "keep-last-valid",
    "precedence": [
      "edition-baseline",
      "last-known-valid",
      "local-exception-none"
    ]
  },
  "inventory": {
    "device_state_file": "/var/lib/w4/inventory/device-state.json",
    "transport": "local-only",
    "owner": "business-pilot",
    "minimum_fields": [
      "profile_id",
      "hostname",
      "policy_state",
      "last_sync_at",
      "update_channel"
    ]
  },
  "deferred_steps": [
    "signed-policy-manifests",
    "policy-revision-feed",
    "local-exception-model",
    "remote-inventory-sync",
    "fleet-compliance-reporting"
  ]
}
JSON;

        $remoteInventory = <<<'JSON'
{
  "schema_version": 1,
  "kind": "business-remote-minimum-inventory",
  "profile_id": "w4-os-business",
  "scope": {
    "mode": "remote-inventory-minimum",
    "backend_required": false,
    "activation": "configured-or-enrolled",
    "delivery_state": "deferred-ready"
  },
  "payload": {
    "source_device_state_file": "/var/lib/w4/inventory/device-state.json",
    "queue_file": "/var/lib/w4/inventory/remote/pending/device-state.json",
    "last_delivery_file": "/var/lib/w4/inventory/remote/last-delivery.json",
    "last_payload_file": "/var/lib/w4/inventory/remote/last-payload.json",
    "minimum_fields": [
      "profile_id",
      "hostname",
      "policy_state",
      "last_sync_at",
      "update_channel",
      "policy_revision"
    ],
    "redacted_fields": [
      "local_username",
      "installed_packages",
      "recent_documents"
    ]
  },
  "remote": {
    "endpoint_config_file": "/etc/w4/inventory/remote-endpoint.json",
    "transport": "https-json-manifest",
    "transport_security": "tls-required",
    "authentication_mode": "device-credential",
    "delivery_mode": "deferred-push",
    "retry_policy": "local-backoff",
    "max_batch_size": 1
  },
  "boundaries": {
    "policy_authority": "local-baseline",
    "inventory_authority": "local-device-state",
    "remote_writeback": "inventory-only",
    "update_windows_deferred": true
  },
  "deferred_steps": [
    "credential-bound-transport",
    "inventory-reconciliation",
    "fleet-reporting",
    "update-window-cohort-feed",
    "operator-portal"
  ]
}
JSON;

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'pilot-local-state.json',
            $localState
        );
        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'remote-minimum-inventory.json',
            $remoteInventory
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-pilot-local-state.json',
            $localState
        );
        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-remote-minimum-inventory.json',
            $remoteInventory
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . 'w4-os-business.profile.json',
            <<<'JSON'
{
  "schema_version": 1,
  "kind": "edition-profile",
  "id": "w4-os-business",
  "features": [
    "desktop-defaults",
    "business-policy-hooks",
    "device-enrollment-ready",
    "inventory-ready",
    "inventory-remote-ready",
    "kde-sddm-default-route",
    "reversible-branding-defaults"
  ]
}
JSON
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'edition-policy.json',
            <<<'JSON'
{
  "profile_id": "w4-os-business",
  "branding": {
    "edition": "Business",
    "hostname_prefix": "w4-business"
  },
  "boot": {
    "default_target": "graphical.target"
  },
  "storage": {
    "root_filesystem": "btrfs"
  },
  "update": {
    "snapshot_backend": "btrfs"
  }
}
JSON
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'installation-plan.json',
            <<<'JSON'
{
  "profile_id": "w4-os-business",
  "storage": {
    "btrfs": {
      "subvolumes": [
        {
          "name": "@",
          "mountpoint": "/"
        },
        {
          "name": "@inventory",
          "mountpoint": "/var/lib/w4"
        }
      ]
    }
  }
}
JSON
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'install.env',
            <<<'ENV'
export W4_BUNDLE_DIR=".."
export W4_EDITION_POLICY_FILE="../edition-policy.json"
export W4_POLICY_BASELINE_SOURCE="../edition-policy.json"
export W4_POLICY_BASELINE_RUNTIME_ENV="/etc/w4/edition-policy.env"
export W4_POLICY_LAST_KNOWN_FILE="/var/lib/w4/policy/last-known-policy.json"
export W4_POLICY_EFFECTIVE_FILE="/var/lib/w4/policy/effective-policy.json"
export W4_POLICY_INVALID_BEHAVIOR="keep-last-valid"
export W4_INVENTORY_DEVICE_STATE_FILE="/var/lib/w4/inventory/device-state.json"
export W4_INVENTORY_TRANSPORT="local-only"
export W4_REMOTE_INVENTORY_SOURCE_FILE="/var/lib/w4/inventory/device-state.json"
export W4_REMOTE_INVENTORY_QUEUE_FILE="/var/lib/w4/inventory/remote/pending/device-state.json"
export W4_REMOTE_INVENTORY_LAST_DELIVERY_FILE="/var/lib/w4/inventory/remote/last-delivery.json"
export W4_REMOTE_INVENTORY_LAST_PAYLOAD_FILE="/var/lib/w4/inventory/remote/last-payload.json"
export W4_REMOTE_INVENTORY_ENDPOINT_FILE="/etc/w4/inventory/remote-endpoint.json"
export W4_REMOTE_INVENTORY_TRANSPORT="https-json-manifest"
export W4_REMOTE_INVENTORY_AUTH_MODE="device-credential"
export W4_REMOTE_INVENTORY_ACTIVATION="configured-or-enrolled"
ENV
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
