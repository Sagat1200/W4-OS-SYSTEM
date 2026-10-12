<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiCliTest extends TestCase
{
    public function testCapabilitiesResponseListsSupportedMethods(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--capabilities',
            '--request-id',
            'cap-001',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('1.0', $payload['api_version']);
        self::assertSame('cap-001', $payload['request_id']);
        self::assertNull($payload['error']);
        self::assertSame('system.capabilities', $payload['result']['method']);
        self::assertSame('system-api-capabilities', $payload['result']['resource']['kind']);
        self::assertContains('profiles.list', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-runtime', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('updates.plan', array_column($payload['result']['data']['methods'], 'name'));
    }

    public function testDbusRuntimeMethodReturnsRuntimeDiagnosis(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-runtime-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-runtime-probe', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['kind']);
        self::assertArrayHasKey('missing_capabilities', $payload['result']['data']);
    }

    public function testProfilesGetReusesW4ctlContract(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-profile-001',
            'method' => 'profiles.get',
            'params' => [
                'profile_id' => 'w4-os-home',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('profiles.get', $payload['result']['method']);
        self::assertSame('edition-profile', $payload['result']['resource']['kind']);
        self::assertSame('w4-os-home', $payload['result']['resource']['id']);
        self::assertSame('profile:show', $payload['result']['data']['command']);
        self::assertSame('w4-os-home', $payload['result']['data']['profile']['id']);
        self::assertContains('gdm3', $payload['result']['data']['resolved_profile']['required_packages']);
    }

    public function testUpdatesPlanWritesCanonicalPlan(): void
    {
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-update-plan-' . bin2hex(random_bytes(6)) . '.json';

        try {
            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-update-001',
                'method' => 'updates.plan',
                'params' => [
                    'request_path' => 'examples/update/home-lab-to-1.0.1.update-request.json',
                    'operation_id' => 'system-api-update-001',
                    'output_path' => $outputPath,
                ],
            ];

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.plan', $payload['result']['method']);
            self::assertSame('update-plan', $payload['result']['resource']['kind']);
            self::assertSame('update:plan', $payload['result']['data']['command']);
            self::assertSame('system-api-update-001', $payload['result']['data']['operation_id']);
            self::assertSame($this->normalizePath($outputPath), $payload['result']['data']['output_path']);

            $plan = $this->decodeJsonFile($outputPath);
            self::assertSame('update-plan', $plan['kind']);
            self::assertSame('system-api-update-001', $plan['operation_id']);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    public function testUnsupportedMethodReturnsProtocolErrorPayload(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-unsupported-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('NOT_SUPPORTED', $payload['error']['code']);
        self::assertStringContainsString('Metodo no soportado', $payload['error']['message']);
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__));
        self::assertIsResource($process, 'No se pudo ejecutar read_system_api.php');

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

    private function normalizePath(string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'read_system_api.php';
    }
}
