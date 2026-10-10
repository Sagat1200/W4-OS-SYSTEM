<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class BusinessEnrollmentReadinessCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-business-enrollment-readiness-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testValidateBusinessEnrollmentReadinessReturnsExpectedPayload(): void
    {
        $this->seedWorkspace();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_enrollment_readiness.php',
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
        self::assertSame('business-enrollment-readiness-validation', $payload['kind']);
        self::assertSame('w4-os-business', $payload['profile_id']);
        self::assertContains('device-enrollment-ready', $payload['feature_flags']);
        self::assertContains('inventory-ready', $payload['feature_flags']);
        self::assertSame('w4-business', $payload['installation_identity']['hostname_prefix']);
        self::assertSame('/var/lib/w4/policy/last-known-policy.json', $payload['policy_contract']['last_known_policy_file']);
        self::assertSame('/var/lib/w4/inventory/device-state.json', $payload['inventory_contract']['device_state_file']);
        self::assertSame('../edition-policy.json', $payload['runtime_exports']['W4_EDITION_POLICY_FILE']);
        self::assertSame('available', $payload['capabilities'][0]['status']);
        self::assertContains('device-keypair-materialization', $payload['deferred_steps']);
    }

    public function testValidateBusinessEnrollmentReadinessSupportsTextFormat(): void
    {
        $this->seedWorkspace();

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_enrollment_readiness.php',
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
        self::assertStringContainsString('Business enrollment readiness', $result['stdout']);
        self::assertStringContainsString('Hostname prefix: w4-business', $result['stdout']);
        self::assertStringContainsString('Contrato local de ultima politica valida: available', $result['stdout']);
        self::assertStringContainsString('Ruta local para estado de inventario: available', $result['stdout']);
    }

    private function seedWorkspace(): void
    {
        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'enrollment-readiness.json',
            <<<'JSON'
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
  "enrollment": {
    "token": {
      "mode": "one-time",
      "organization_scope": "required",
      "expiration": "required",
      "stored_on_image": false
    },
    "request": {
      "proof_of_possession": "required",
      "idempotency_key": "required"
    },
    "credential": {
      "issued_during_enrollment": true,
      "stored_on_image": false
    }
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
JSON
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-enrollment-readiness.json',
            file_get_contents($this->workspaceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'enrollment-readiness.json') ?: ''
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'policy.json',
            <<<'JSON'
{
  "schema_version": 1,
  "profile_id": "w4-os-business",
  "branding": {
    "edition": "Business",
    "hostname_prefix": "w4-business"
  },
  "boot": {
    "default_target": "graphical.target"
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
  "enterprise_agent": {
    "installed": false,
    "enrolled": false
  }
}
JSON
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
  "enterprise_agent": {
    "installed": false,
    "enrolled": false
  }
}
JSON
        );

        $this->writeFile(
            $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'w4-os-business' . DIRECTORY_SEPARATOR . 'installation-plan.json',
            <<<'JSON'
{
  "profile_id": "w4-os-business",
  "identity": {
    "hostname": "w4-business-vm",
    "user": {
      "username": "w4admin",
      "password_source": "secret://install/business-local-password"
    }
  },
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
export W4_DEFAULT_TARGET="graphical.target"
export W4_HOSTNAME_PREFIX="w4-business"
export W4_SSH_ENABLED="0"
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
