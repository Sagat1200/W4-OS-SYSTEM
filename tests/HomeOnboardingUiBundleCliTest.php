<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class HomeOnboardingUiBundleCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-home-onboarding-ui-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testGenerateHomeOnboardingUiBundleCreatesExpectedArtifacts(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . 'w4-os-home';
        $this->seedLiveOutput($liveOutputDir);

        $outputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'home-onboarding-ui';
        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_home_onboarding_ui_bundle.php',
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
        self::assertSame('home-onboarding-ui.json', $payload['routes']['data']);
        self::assertSame(5, $payload['visible_step_count']);
        self::assertSame(4, $payload['deferred_step_count']);
        self::assertContains('styles/home-onboarding.css', $payload['generated_files']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'home-onboarding-ui-manifest.json');
        self::assertSame('home-onboarding-light-ui-bundle-manifest', $manifest['kind']);
        self::assertSame('home-onboarding-light-ui-bundle', $manifest['source_kind']);
        self::assertSame(5, $manifest['visible_step_count']);
        self::assertSame('index.html', $manifest['routes']['home']);

        $bundle = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'home-onboarding-ui.json');
        self::assertSame('home-onboarding-light-ui-bundle', $bundle['kind']);
        self::assertSame('w4-os-home', $bundle['profile_id']);
        self::assertSame('graphical.target', $bundle['default_target']);
        self::assertCount(5, $bundle['visible_steps']);
        self::assertSame('settings', $bundle['visible_steps'][2]['id']);
        self::assertSame('w4-control-center-home-home.desktop', $bundle['visible_steps'][2]['entrypoint']);
        self::assertSame('telemetry-opt-in-ui', $bundle['deferred_steps'][3]['id']);

        $html = $this->readFile($outputDir . DIRECTORY_SEPARATOR . 'index.html');
        self::assertStringContainsString('<title>Home Onboarding · w4-os-home</title>', $html);
        self::assertStringContainsString('Primer inicio ligero', $html);
        self::assertStringContainsString('Abrir W4 Settings', $html);
        self::assertStringContainsString('telemetry-opt-in-ui', $html);
    }

    private function seedLiveOutput(string $liveOutputDir): void
    {
        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-home"
W4_PROFILE_NAME="W4 OS Home"
W4_LIVE_USER="w4live"
W4_LIVE_HOSTNAME="w4-home-live"
W4_DEFAULT_TARGET="graphical.target"
W4_GENERATED_AT="2026-10-09T06:10:00Z"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            <<<'ENV'
W4_PRODUCT_NAME="W4 OS System"
W4_DISTRIBUTION_NAME="W4 OS"
W4_PROFILE_ID="w4-os-home"
W4_PROFILE_NAME="W4 OS Home"
W4_EDITION="Home"
W4_HOSTNAME="w4-home"
W4_LIVE_HOSTNAME="w4-home-live"
W4_LIVE_USER="w4live"
W4_DEFAULT_TARGET="graphical.target"
W4_FEATURES="desktop-defaults,gnome-gdm-default-route,home-onboarding,local-backup-ready,reversible-branding-defaults"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'w4-firstboot.service',
            <<<'UNIT'
[Unit]
Description=W4 OS first boot initialization (Home)
After=local-fs.target systemd-machine-id-commit.service
ConditionPathExists=!/var/lib/w4/firstboot-complete

[Service]
Type=oneshot
ExecStart=/usr/local/lib/w4/w4-firstboot.sh
RemainAfterExit=yes

[Install]
WantedBy=graphical.target
UNIT
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'w4-live-prep.service',
            <<<'UNIT'
[Unit]
Description=W4 OS live session preparation (Home)
After=local-fs.target systemd-machine-id-commit.service
Before=display-manager.service getty@tty1.service

[Service]
Type=oneshot
ExecStart=/usr/local/lib/w4/w4-live-prep.sh
RemainAfterExit=yes

[Install]
WantedBy=graphical.target
UNIT
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-firstboot.sh',
            <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

STATE_DIR="/var/lib/w4"
STATE_FILE="${STATE_DIR}/firstboot-complete"

mkdir -p /etc/w4
cat > /etc/w4/firstboot-state.env <<EOF
W4_FIRSTBOOT_COMPLETED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF
BASH
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-live-prep.sh',
            <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

LIVE_USER="${W4_LIVE_USER:-w4live}"
if ! id "${LIVE_USER}" >/dev/null 2>&1; then
  useradd -m -s /bin/bash "${LIVE_USER}"
fi

cat > /etc/w4/live-state.env <<EOF
W4_LIVE_USER="${LIVE_USER}"
EOF

cat > /etc/systemd/system/getty@tty1.service.d/autologin.conf <<EOF
[Service]
ExecStart=-/sbin/agetty --autologin ${LIVE_USER} --noclear %I $TERM
EOF
BASH
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'graphical.target.wants' . DIRECTORY_SEPARATOR . 'w4-firstboot.service',
            "../w4-firstboot.service\n"
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'graphical.target.wants' . DIRECTORY_SEPARATOR . 'w4-live-prep.service',
            "../w4-live-prep.service\n"
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
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded, 'No se pudo decodificar JSON');

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        self::assertFileExists($path);
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        return $this->decodeJson($contents);
    }

    private function readFile(string $path): string
    {
        self::assertFileExists($path);
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        return $contents;
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true), sprintf('No se pudo crear %s', $directory));
        }

        self::assertNotFalse(file_put_contents($path, $contents), sprintf('No se pudo escribir %s', $path));
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

            @unlink($path);
        }

        @rmdir($directory);
    }
}
