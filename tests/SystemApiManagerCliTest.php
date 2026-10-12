<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiManagerCliTest extends TestCase
{
    public function testGetCapabilitiesReturnsBindingMetadata(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'get-capabilities',
            '--request-id',
            'manager-cap-001',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('manager-cap-001', $payload['request_id']);
        self::assertNull($payload['error']);
        self::assertSame('system.capabilities', $payload['result']['method']);
        self::assertSame('local-manager-ready', $payload['result']['data']['binding']['status']);
        self::assertSame('org.w4.System1', $payload['result']['data']['binding']['dbus']['name']);
        self::assertContains('Call', $payload['result']['data']['binding']['dbus']['methods']);
    }

    public function testCallDispatchesProfilesGet(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'manager-profile-001',
            'method' => 'profiles.get',
            'params' => [
                'profile_id' => 'w4-os-home',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('profiles.get', $payload['result']['method']);
        self::assertSame('w4-os-home', $payload['result']['resource']['id']);
        self::assertSame('profile:show', $payload['result']['data']['command']);
    }

    public function testCallReturnsProtocolErrorForInvalidJson(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'call',
            '--request-json',
            '{bad json}',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('invalid-request', $payload['request_id']);
        self::assertNull($payload['result']);
        self::assertSame('VALIDATION_ERROR', $payload['error']['code']);
        self::assertStringContainsString('Request JSON invalido', $payload['error']['message']);
    }

    public function testCallViaRequestFileWritesOutput(): void
    {
        $requestPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-manager-request-' . bin2hex(random_bytes(6)) . '.json';
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-manager-output-' . bin2hex(random_bytes(6)) . '.json';

        try {
            file_put_contents(
                $requestPath,
                json_encode([
                    'api_version' => '1.0',
                    'request_id' => 'manager-update-001',
                    'method' => 'updates.plan',
                    'params' => [
                        'request_path' => 'examples/update/home-lab-to-1.0.1.update-request.json',
                        'operation_id' => 'manager-update-op-001',
                        'output_path' => $outputPath,
                    ],
                ], JSON_THROW_ON_ERROR)
            );

            $result = $this->runPhpScript([
                $this->scriptPath(),
                'call',
                '--request-file',
                $requestPath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.plan', $payload['result']['method']);
            self::assertSame('manager-update-op-001', $payload['result']['data']['operation_id']);

            $plan = $this->decodeJsonFile($outputPath);
            self::assertSame('update-plan', $plan['kind']);
            self::assertSame('manager-update-op-001', $plan['operation_id']);
        } finally {
            if (is_file($requestPath)) {
                unlink($requestPath);
            }

            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
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
        self::assertIsResource($process, 'No se pudo ejecutar system_api_manager.php');

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

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_manager.php';
    }
}
